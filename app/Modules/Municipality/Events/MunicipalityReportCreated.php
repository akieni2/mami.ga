<?php

namespace App\Modules\Municipality\Events;

use App\Modules\Municipality\Models\MunicipalityReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MunicipalityReportCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public MunicipalityReport $report,
    ) {}
}
