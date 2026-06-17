<?php

namespace App\Modules\Municipality\Listeners;

use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Events\MunicipalityReportStatusChanged;
use App\Notifications\MunicipalityReportStatusNotification;

class NotifyCitizenOnReportStatusChange
{
    public function handle(MunicipalityReportStatusChanged $event): void
    {
        $report = $event->report->loadMissing('citizen');
        $citizen = $report->citizen;

        if ($citizen === null) {
            return;
        }

        $notifiableStatuses = [
            ReportStatus::Assigned,
            ReportStatus::InProgress,
            ReportStatus::Resolved,
            ReportStatus::Closed,
        ];

        if (! in_array($event->toStatus, $notifiableStatuses, true)) {
            return;
        }

        $citizen->notify(new MunicipalityReportStatusNotification($report, $event->toStatus));
    }
}
