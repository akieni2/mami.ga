<?php

namespace App\Modules\Municipality\Services;

use App\Models\User;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Location;
use App\Modules\Municipality\Enums\OperatorPhotoPurpose;
use App\Modules\Municipality\Enums\SyncStatus;
use App\Modules\Municipality\Enums\TaxStatus;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorTaxStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EconomicOperatorService
{
    public function __construct(
        private readonly EconomicOperatorReferenceGenerator $referenceGenerator,
        private readonly TerritorialResolverService $territorialResolver,
        private readonly EconomicOperatorAuditService $auditService,
        private readonly QRCodeManagement $qrCodeManagement,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $photos
     */
    public function enroll(User $agent, array $data, array $photos): EconomicOperator
    {
        $this->assertGpsAccuracy((float) $data['gps_accuracy_m']);

        if (! isset($photos[OperatorPhotoPurpose::Facade->value]) || $photos[OperatorPhotoPurpose::Facade->value] === null) {
            throw ValidationException::withMessages([
                'facade' => ['La photo de façade est obligatoire.'],
            ]);
        }

        return DB::transaction(function () use ($agent, $data, $photos): EconomicOperator {
            $territory = $this->territorialResolver->resolve(
                (float) $data['latitude'],
                (float) $data['longitude'],
            );

            $syncStatus = isset($data['sync_status'])
                ? SyncStatus::from($data['sync_status'])
                : SyncStatus::Synced;

            $operator = EconomicOperator::query()->create([
                'public_id' => $this->referenceGenerator->next(),
                'territory_id' => $territory['territory_id'],
                'sector_id' => $territory['sector_id'],
                'operational_zone_id' => $territory['operational_zone_id'],
                'economic_zone_id' => $territory['economic_zone_id'],
                'arrondissement_sector_id' => $territory['arrondissement_sector_id'],
                'secteur' => $territory['arrondissement_name'],
                'category_id' => $data['category_id'],
                'commercial_name' => $data['commercial_name'],
                'activity_label' => $data['activity_label'],
                'responsible_name' => $data['responsible_name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'gps_accuracy_m' => $data['gps_accuracy_m'],
                'gps_captured_at' => $data['gps_captured_at'] ?? now(),
                'sync_status' => $syncStatus,
                'registration_date' => now()->toDateString(),
                'registered_by' => $agent->id,
                'last_modified_by' => $agent->id,
                'current_tax_status' => TaxStatus::A_Jour,
                'is_active' => true,
            ]);

            Location::query()->create([
                'locatable_type' => 'economic_operator',
                'locatable_id' => $operator->id,
                'latitude' => $operator->latitude,
                'longitude' => $operator->longitude,
                'recorded_at' => $operator->gps_captured_at,
                'context' => 'enrollment',
            ]);

            EconomicOperatorTaxStatus::query()->create([
                'economic_operator_id' => $operator->id,
                'status' => TaxStatus::A_Jour,
                'effective_from' => now()->toDateString(),
                'assessed_by' => $agent->id,
                'notes' => 'Enregistrement terrain initial',
            ]);

            foreach ($photos as $purpose => $file) {
                if ($file instanceof UploadedFile) {
                    $this->storePhoto($operator, $agent, $file, OperatorPhotoPurpose::from($purpose));
                }
            }

            $this->auditService->log($agent, $operator, 'operator.enrolled', [
                'public_id' => $operator->public_id,
                'latitude' => (float) $operator->latitude,
                'longitude' => (float) $operator->longitude,
                'gps_accuracy_m' => (float) $operator->gps_accuracy_m,
                'sector_id' => $operator->sector_id,
                'economic_zone_id' => $operator->economic_zone_id,
            ]);

            $this->qrCodeManagement->generateForOperator($operator);

            return $operator->fresh([
                'category',
                'sector',
                'operationalZone',
                'economicZone',
                'arrondissement',
                'registeredBy',
                'attachments',
                'activeQrcode',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $agent, EconomicOperator $operator, array $data): EconomicOperator
    {
        return DB::transaction(function () use ($agent, $operator, $data): EconomicOperator {
            $operator->fill(array_merge($data, [
                'last_modified_by' => $agent->id,
            ]));
            $operator->save();

            $this->auditService->log($agent, $operator, 'operator.updated', $data);

            return $operator->fresh([
                'category',
                'sector',
                'operationalZone',
                'economicZone',
                'arrondissement',
                'registeredBy',
                'attachments',
            ]);
        });
    }

    public function recordInspection(User $agent, EconomicOperator $operator, ?string $notes = null): EconomicOperator
    {
        return DB::transaction(function () use ($agent, $operator, $notes): EconomicOperator {
            $operator->update([
                'last_visit_at' => now(),
                'last_modified_by' => $agent->id,
            ]);

            $this->auditService->log($agent, $operator, 'operator.inspected', [
                'notes' => $notes,
                'visited_at' => now()->toIso8601String(),
            ]);

            return $operator->fresh();
        });
    }

    public function assertGpsAccuracy(float $accuracyM): void
    {
        $max = (float) config('municipality.gps_max_accuracy_m', 20);

        if ($accuracyM > $max) {
            throw ValidationException::withMessages([
                'gps_accuracy_m' => ['Position GPS insuffisamment précise. Veuillez patienter.'],
            ]);
        }
    }

    private function storePhoto(
        EconomicOperator $operator,
        User $agent,
        UploadedFile $photo,
        OperatorPhotoPurpose $purpose,
    ): Attachment {
        $path = $photo->store('economic-operators/'.$operator->id, 'public');

        return Attachment::query()->create([
            'attachable_type' => 'economic_operator',
            'attachable_id' => $operator->id,
            'disk' => 'public',
            'path' => $path,
            'mime_type' => $photo->getMimeType(),
            'size_bytes' => $photo->getSize(),
            'purpose' => $purpose->value,
            'uploaded_by' => $agent->id,
        ]);
    }
}
