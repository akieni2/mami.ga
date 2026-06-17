<?php

namespace App\Modules\Taxi;

use App\Modules\Core\Support\AbstractModule;
use Illuminate\Support\ServiceProvider;

/**
 * Module Taxi — code métier existant dans app/Http, app/Services, app/Models.
 * Les routes API historiques restent dans routes/api.php (compatibilité 100 %).
 */
class TaxiModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
