<?php

namespace App\Modules\Municipality\Events;

use App\Modules\Municipality\Enums\ReportStatus;
use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MunicipalityReportStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MunicipalityReport $report,
        public ReportStatus $fromStatus,
        public ReportStatus $toStatus,
    ) {}
}
