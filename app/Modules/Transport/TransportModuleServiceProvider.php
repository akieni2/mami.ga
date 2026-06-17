<?php

namespace App\Modules\Transport;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TransportModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/transport')
            ->middleware('api')
            ->group(base_path('app/Modules/Transport/Routes/api.php'));
    }
}
