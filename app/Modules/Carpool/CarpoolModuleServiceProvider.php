<?php

namespace App\Modules\Carpool;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CarpoolModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/carpool')
            ->middleware('api')
            ->group(base_path('app/Modules/Carpool/Routes/api.php'));
    }
}
