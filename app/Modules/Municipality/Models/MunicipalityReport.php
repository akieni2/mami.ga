<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Location;
use App\Modules\Municipality\Enums\ReportCategory;
use App\Modules\Municipality\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MunicipalityReport extends Model
{
    protected $fillable = [
        'reference',
        'citizen_id',
        'category',
        'title',
        'description',
        'latitude',
        'longitude',
        'address',
        'territory_id',
        'sector_id',
        'operational_zone_id',
        'status',
        'assigned_to',
        'resolved_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'category' => ReportCategory::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'citizen_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(MunicipalTerritory::class, 'territory_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'sector_id');
    }

    public function operationalZone(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'operational_zone_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(MunicipalityReportUpdate::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }
}
