<?php

namespace App\Modules\Commerce;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CommerceModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/commerce')
            ->middleware('api')
            ->group(base_path('app/Modules/Commerce/Routes/api.php'));
    }
}
