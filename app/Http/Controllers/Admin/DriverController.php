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
}
