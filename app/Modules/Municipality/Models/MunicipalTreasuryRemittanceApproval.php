<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceApprovalAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalTreasuryRemittanceApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'remittance_id',
        'action',
        'performed_by',
        'comments',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'action' => TreasuryRemittanceApprovalAction::class,
            'created_at' => 'datetime',
        ];
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(MunicipalTreasuryRemittance::class, 'remittance_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
