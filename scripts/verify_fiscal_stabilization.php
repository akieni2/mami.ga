<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Modules\Municipality\Models\MunicipalTaxType;
use App\Modules\Municipality\Services\TaxTypeService;
use Illuminate\Support\Facades\Route;

echo "=== Sprint 3.1 — Vérification fiscalité ===\n\n";

foreach ([
    'admin.municipality.fiscal.tax-types.store',
    'admin.municipality.fiscal.rates.store',
    'admin.municipality.fiscal.targets.store',
    'admin.municipality.fiscal.assignments.store',
    'admin.municipality.fiscal.obligations.generate',
] as $name) {
    $route = Route::getRoutes()->getByName($name);
    echo ($route ? 'OK' : 'MISSING')." route: {$name}\n";
    if ($route) {
        echo "  → {$route->methods()[0]} {$route->uri()}\n";
    }
}

$admin = User::query()->where('is_admin', true)->first();
if ($admin === null) {
    echo "\nAucun admin — skip création test\n";
    exit(0);
}

$before = MunicipalTaxType::query()->count();
$service = app(TaxTypeService::class);

try {
    $service->assertCodeAvailable('TAX-STAB-TEST');
    $created = $service->create($admin, [
        'code' => 'TAX-STAB-TEST',
        'name' => 'Taxe stabilisation',
        'description' => 'Test script Sprint 3.1',
    ]);
    echo "\nCréation service OK — id={$created->id} code={$created->code}\n";
} catch (Throwable $e) {
    echo "\nErreur création: ".$e->getMessage()."\n";
}

$after = MunicipalTaxType::query()->count();
echo "Count tax_types: {$before} → {$after}\n";

// cleanup test row
MunicipalTaxType::query()->where('code', 'TAX-STAB-TEST')->delete();
echo "Nettoyage effectué.\n";
