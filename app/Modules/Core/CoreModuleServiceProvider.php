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
            'municipality_report' => \App\Modules\Municipality\Models\MunicipalityReport::class,
            'economic_operator' => \App\Modules\Municipality\Models\EconomicOperator::class,
            'municipal_tax_type' => \App\Modules\Municipality\Models\MunicipalTaxType::class,
            'municipal_tax_rate' => \App\Modules\Municipality\Models\MunicipalTaxRate::class,
            'municipal_collection_target' => \App\Modules\Municipality\Models\MunicipalCollectionTarget::class,
            'operator_tax_assignment' => \App\Modules\Municipality\Models\OperatorTaxAssignment::class,
            'fiscal_obligation' => \App\Modules\Municipality\Models\FiscalObligation::class,
            'municipal_payment' => \App\Modules\Municipality\Models\MunicipalPayment::class,
            'cash_session' => \App\Modules\Municipality\Models\CashSession::class,
        ]);
    }
}
