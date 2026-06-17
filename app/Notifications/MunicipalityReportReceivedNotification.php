<?php

namespace App\Notifications;

use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MunicipalityReportReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public MunicipalityReport $report,
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
            'type' => 'municipality_report_received',
            'report_id' => $this->report->id,
            'reference' => $this->report->reference,
            'message' => 'Votre signalement '.$this->report->reference.' a bien été reçu.',
        ];
    }
}
