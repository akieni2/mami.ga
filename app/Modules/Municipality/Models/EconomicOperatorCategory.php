<?php

namespace App\Modules\Municipality\Models;

use App\Modules\Municipality\Enums\EconomicZoneKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicOperatorCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'parent_id',
        'icon',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function operators(): HasMany
    {
        return $this->hasMany(EconomicOperator::class, 'category_id');
    }
}
