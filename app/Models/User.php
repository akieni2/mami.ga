<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Modules\Core\Models\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function clientRides(): HasMany
    {
        return $this->hasMany(Ride::class, 'client_id');
    }

    public function driverApplication(): HasOne
    {
        return $this->hasOne(DriverApplication::class)->latestOfMany();
    }

    public function driverApplications(): HasMany
    {
        return $this->hasMany(DriverApplication::class);
    }

    public function isDriver(): bool
    {
        return $this->driver()->exists();
    }

    public function isAdmin(): bool
    {
        if ($this->hasRole('admin') || $this->hasRole('super_admin')) {
            return true;
        }

        return (bool) $this->is_admin;
    }

    public function isMunicipalSupervisor(): bool
    {
        return $this->hasRole('municipal_supervisor');
    }

    public function canAccessEconomicOperatorAdmin(): bool
    {
        return $this->isAdmin() || $this->isMunicipalSupervisor();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_at', 'assigned_by']);
    }

    public function hasRole(string $slug): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    public function hasPermission(string $slug): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $slug))
            ->exists();
    }
}
