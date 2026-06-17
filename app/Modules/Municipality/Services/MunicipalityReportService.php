<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Location;
use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Events\MunicipalityReportCreated;
use App\Modules\Municipality\Events\MunicipalityReportStatusChanged;
use App\Modules\Municipality\Models\MunicipalityReport;
use App\Modules\Municipality\Models\MunicipalityReportUpdate;
use App\Notifications\MunicipalityReportReceivedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MunicipalityReportService
{
    public function __construct(
        private readonly MunicipalityReportReferenceGenerator $referenceGenerator,
        private readonly TerritorialResolverService $territorialResolver,
        private readonly MunicipalityAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $citizen, array $data, ?UploadedFile $photo = null): MunicipalityReport
    {
        return DB::transaction(function () use ($citizen, $data, $photo): MunicipalityReport {
            $territory = $this->territorialResolver->resolve(
                (float) $data['latitude'],
                (float) $data['longitude'],
            );

            $report = MunicipalityReport::query()->create([
                'reference' => $this->referenceGenerator->next(),
                'citizen_id' => $citizen->id,
                'category' => $data['category'],
                'title' => $data['title'],
                'description' => $data['description'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'address' => $data['address'] ?? null,
                'territory_id' => $territory['territory_id'],
                'sector_id' => $territory['sector_id'],
                'operational_zone_id' => $territory['operational_zone_id'],
                'status' => ReportStatus::New,
            ]);

            Location::query()->create([
                'locatable_type' => 'municipality_report',
                'locatable_id' => $report->id,
                'latitude' => $report->latitude,
                'longitude' => $report->longitude,
                'recorded_at' => now(),
                'context' => 'report_created',
            ]);

            if ($photo !== null) {
                $this->storePhoto($report, $citizen, $photo);
            }

            MunicipalityReportUpdate::query()->create([
                'municipality_report_id' => $report->id,
                'user_id' => $citizen->id,
                'from_status' => null,
                'to_status' => ReportStatus::New,
                'notes' => 'Signalement créé',
            ]);

            $this->auditService->log($citizen, $report, 'report.created', [
                'reference' => $report->reference,
                'category' => $report->category->value,
            ]);

            $citizen->notify(new MunicipalityReportReceivedNotification($report));

            event(new MunicipalityReportCreated($report));

            return $report->fresh(['attachments', 'sector', 'operationalZone']);
        });
    }

    public function assign(User $actor, MunicipalityReport $report, int $assigneeId, ?string $notes = null): MunicipalityReport
    {
        $this->assertTransitionAllowed($report->status, ReportStatus::Assigned);

        return $this->transition($actor, $report, ReportStatus::Assigned, [
            'assigned_to' => $assigneeId,
        ], $notes ?? 'Signalement assigné');
    }

    public function updateStatus(
        User $actor,
        MunicipalityReport $report,
        ReportStatus $status,
        ?string $notes = null,
    ): MunicipalityReport {
        $this->assertTransitionAllowed($report->status, $status);

        $extra = [];
        if ($status === ReportStatus::Resolved) {
            $extra['resolved_at'] = now();
        }
        if ($status === ReportStatus::Closed) {
            $extra['closed_at'] = now();
        }

        return $this->transition($actor, $report, $status, $extra, $notes);
    }

    /**
     * @param  array<string, mixed>  $extraAttributes
     */
    private function transition(
        User $actor,
        MunicipalityReport $report,
        ReportStatus $toStatus,
        array $extraAttributes = [],
        ?string $notes = null,
    ): MunicipalityReport {
        return DB::transaction(function () use ($actor, $report, $toStatus, $extraAttributes, $notes): MunicipalityReport {
            $fromStatus = $report->status;

            $report->fill(array_merge($extraAttributes, ['status' => $toStatus]));
            $report->save();

            MunicipalityReportUpdate::query()->create([
                'municipality_report_id' => $report->id,
                'user_id' => $actor->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'notes' => $notes,
            ]);

            $this->auditService->log($actor, $report, 'report.status_changed', [
                'from' => $fromStatus->value,
                'to' => $toStatus->value,
                'notes' => $notes,
            ]);

            event(new MunicipalityReportStatusChanged($report, $fromStatus, $toStatus));

            return $report->fresh(['attachments', 'sector', 'operationalZone', 'assignee']);
        });
    }

    private function assertTransitionAllowed(ReportStatus $from, ReportStatus $to): void
    {
        $allowed = match ($from) {
            ReportStatus::New => [ReportStatus::Assigned, ReportStatus::InProgress],
            ReportStatus::Assigned => [ReportStatus::InProgress, ReportStatus::Resolved],
            ReportStatus::InProgress => [ReportStatus::Resolved, ReportStatus::Closed],
            ReportStatus::Resolved => [ReportStatus::Closed],
            ReportStatus::Closed => [],
        };

        if (! in_array($to, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Transition invalide : {$from->value} → {$to->value}"],
            ]);
        }
    }

    private function storePhoto(MunicipalityReport $report, User $citizen, UploadedFile $photo): Attachment
    {
        $path = $photo->store('municipality-reports/'.$report->id, 'public');

        return Attachment::query()->create([
            'attachable_type' => 'municipality_report',
            'attachable_id' => $report->id,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $photo->getMimeType(),
            'size_bytes' => $photo->getSize(),
            'purpose' => 'report_photo',
            'uploaded_by' => $citizen->id,
        ]);
    }
}
