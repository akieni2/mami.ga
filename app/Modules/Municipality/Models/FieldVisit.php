<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\VisitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldVisit extends Model
{
    protected $fillable = [
        'operator_id',
        'agent_id',
        'visit_type',
        'visit_date',
        'notes',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'visit_type' => VisitType::class,
            'visit_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(EconomicOperator::class, 'operator_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
