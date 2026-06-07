<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\View\View;

class DriverController extends Controller
{
    public function index(): View
    {
        $drivers = Driver::query()
            ->with(['user', 'vehicle'])
            ->latest('id')
            ->paginate(20);

        return view('admin.drivers.index', compact('drivers'));
    }

    public function show(Driver $driver): View
    {
        $driver->load(['user', 'vehicle']);

        return view('admin.drivers.show', compact('driver'));
    }

    public function live(Driver $driver): View
    {
        $driver->load(['user', 'vehicle']);

        return view('admin.drivers.live', compact('driver'));
    }
}
