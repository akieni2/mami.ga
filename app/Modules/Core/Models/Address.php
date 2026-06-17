<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'label',
        'latitude',
        'longitude',
        'source',
        'commune',
        'quartier',
        'plus_code',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'metadata' => 'array',
        ];
    }
}
