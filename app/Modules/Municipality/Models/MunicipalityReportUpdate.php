<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalityReportUpdate extends Model
{
    protected $fillable = [
        'municipality_report_id',
        'user_id',
        'from_status',
        'to_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ReportStatus::class,
            'to_status' => ReportStatus::class,
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(MunicipalityReport::class, 'municipality_report_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
