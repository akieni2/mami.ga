<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use Illuminate\View\View;

class RideController extends Controller
{
    public function index(): View
    {
        $rides = Ride::query()
            ->with(['client', 'driver.user', 'driver.vehicle'])
            ->latest('id')
            ->paginate(20);

        return view('admin.rides.index', compact('rides'));
    }
}
