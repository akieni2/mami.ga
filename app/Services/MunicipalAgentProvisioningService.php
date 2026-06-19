<?php

namespace App\Services;

use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class MunicipalAgentProvisioningService
{
    /**
     * @param  array{name: string, email: string, phone?: string|null, password: string}  $data
     */
    public function create(User $admin, array $data): User
    {
        $role = Role::query()->where('slug', MamiRole::MunicipalAgent->value)->first();

        if ($role === null) {
            throw new RuntimeException('Le rôle agent municipal est introuvable. Exécutez RolePermissionSeeder.');
        }

        return DB::transaction(function () use ($admin, $data, $role): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'is_admin' => false,
            ]);

            $user->roles()->attach($role->id, [
                'assigned_at' => now(),
                'assigned_by' => $admin->id,
            ]);

            return $user->fresh('roles');
        });
    }
}
