<?php

namespace App\Notifications;

use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MunicipalityReportStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MunicipalityReport $report,
        public ReportStatus $status,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'municipality_report_status',
            'report_id' => $this->report->id,
            'reference' => $this->report->reference,
            'status' => $this->status->value,
            'message' => $this->messageForStatus(),
        ];
    }

    private function messageForStatus(): string
    {
        return match ($this->status) {
            ReportStatus::Assigned => 'Votre signalement '.$this->report->reference.' a été pris en charge.',
            ReportStatus::InProgress => 'Votre signalement '.$this->report->reference.' est en cours de traitement.',
            ReportStatus::Resolved => 'Votre signalement '.$this->report->reference.' a été résolu.',
            ReportStatus::Closed => 'Votre signalement '.$this->report->reference.' est clôturé.',
            default => 'Mise à jour de votre signalement '.$this->report->reference.'.',
        };
    }
}
