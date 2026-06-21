<?php

namespace App\Modules\Municipality;

use App\Modules\Municipality\Events\MunicipalityReportStatusChanged;
use App\Modules\Municipality\Listeners\NotifyCitizenOnReportStatusChange;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\MunicipalityReport;
use App\Modules\Municipality\Policies\EconomicOperatorPolicy;
use App\Modules\Municipality\Policies\MunicipalityReportPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class MunicipalityModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/municipality')
            ->middleware('api')
            ->group(base_path('app/Modules/Municipality/Routes/api.php'));

        Gate::policy(MunicipalityReport::class, MunicipalityReportPolicy::class);
        Gate::policy(EconomicOperator::class, EconomicOperatorPolicy::class);

        \Illuminate\Support\Facades\Route::bind('report', function (string $value): MunicipalityReport {
            return MunicipalityReport::query()->findOrFail($value);
        });

        Route::bind('operator', function (string $value): EconomicOperator {
            return EconomicOperator::query()->findOrFail($value);
        });

        Route::bind('mission', function (string $value): \App\Modules\Municipality\Models\FinancialMission {
            return \App\Modules\Municipality\Models\FinancialMission::query()->findOrFail($value);
        });

        Event::listen(
            MunicipalityReportStatusChanged::class,
            NotifyCitizenOnReportStatusChange::class,
        );
    }
}
