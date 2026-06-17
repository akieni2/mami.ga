<?php

namespace App\Modules\Municipality;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MunicipalityModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/municipality')
            ->middleware('api')
            ->group(base_path('app/Modules/Municipality/Routes/api.php'));
    }
}
