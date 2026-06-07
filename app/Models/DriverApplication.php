<?php

namespace App\Models;

use App\Enums\DriverApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DriverApplication extends Model
{
    /** @use HasFactory<\Database\Factories\DriverApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'national_id_number',
        'driving_license_number',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_color',
        'vehicle_year',
        'plate_number',
        'vehicle_type',
        'driver_photo_path',
        'license_photo_path',
        'vehicle_photo_path',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DriverApplicationStatus::class,
            'vehicle_year' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function vehicleLabel(): string
    {
        return trim($this->vehicle_brand.' '.$this->vehicle_model.' ('.$this->plate_number.')');
    }

    public function photoUrl(string $attribute): ?string
    {
        $path = $this->{$attribute};

        if ($path === null || $path === '') {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
