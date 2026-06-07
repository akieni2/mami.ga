<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\User;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = User::query()
            ->where('is_admin', false)
            ->whereDoesntHave('driver')
            ->withCount('clientRides')
            ->orderByDesc('client_rides_count')
            ->paginate(20);

        return view('admin.clients.index', compact('clients'));
    }

    public function show(User $user): View
    {
        abort_unless(! $user->isDriver() && ! $user->is_admin, 404);

        $ridesCount = Ride::query()->where('client_id', $user->id)->count();

        $rides = Ride::query()
            ->with(['driver.user', 'driver.vehicle'])
            ->where('client_id', $user->id)
            ->latest('id')
            ->paginate(15);

        return view('admin.clients.show', compact('user', 'rides', 'ridesCount'));
    }
}
