<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Core\Enums\MamiRole;
use App\Modules\Core\Models\Role;
use App\Services\MunicipalAgentProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function __construct(
        private readonly MunicipalAgentProvisioningService $agentProvisioning,
    ) {}

    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($q) use ($search): void {
                    $q->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('phone', 'like', $search);
                });
            })
            ->when($request->filled('role'), function ($query) use ($request): void {
                $query->whereHas('roles', fn ($q) => $q->where('slug', $request->string('role')));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $roles = Role::query()->orderBy('name')->get();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function show(User $user): View
    {
        $user->load('roles', 'driver');

        return view('admin.users.show', [
            'user' => $user,
            'allRoles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function createAgent(): View
    {
        return view('admin.users.create-agent');
    }

    public function storeAgent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->agentProvisioning->create($request->user(), $data);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Agent municipal créé. Le rôle municipal_agent a été attribué.');
    }

    public function attachRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_slug' => ['required', 'string', 'exists:roles,slug'],
        ]);

        $role = Role::query()->where('slug', $data['role_slug'])->firstOrFail();

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_at' => now(),
                'assigned_by' => $request->user()->id,
            ],
        ]);

        return back()->with('success', 'Rôle « '.$role->name.' » attribué.');
    }

    public function detachRole(Request $request, User $user, string $roleSlug): RedirectResponse
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();

        if (in_array($roleSlug, [MamiRole::Admin->value, MamiRole::SuperAdmin->value], true)
            && $user->id === $request->user()->id) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre rôle administrateur.');
        }

        $user->roles()->detach($role->id);

        return back()->with('success', 'Rôle « '.$role->name.' » retiré.');
    }
}
