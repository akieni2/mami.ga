<?php

namespace App\Modules\Core;

use App\Modules\Carpool\CarpoolModuleServiceProvider;
use App\Modules\Commerce\CommerceModuleServiceProvider;
use App\Modules\Municipality\MunicipalityModuleServiceProvider;
use App\Modules\Taxi\TaxiModuleServiceProvider;
use App\Modules\Transport\TransportModuleServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CoreModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(TaxiModuleServiceProvider::class);
        $this->app->register(CarpoolModuleServiceProvider::class);
        $this->app->register(TransportModuleServiceProvider::class);
        $this->app->register(CommerceModuleServiceProvider::class);
        $this->app->register(MunicipalityModuleServiceProvider::class);
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'ride' => \App\Models\Ride::class,
            'driver' => \App\Models\Driver::class,
            'user' => \App\Models\User::class,
            'address' => \App\Modules\Core\Models\Address::class,
            'payment' => \App\Modules\Core\Models\Payment::class,
        ]);
    }
}
