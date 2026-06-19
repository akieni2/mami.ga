<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use App\Modules\Municipality\Models\MunicipalTaxType;

foreach ([
    'municipal_tax_types',
    'municipal_tax_rates',
    'municipal_collection_targets',
    'operator_tax_assignments',
    'fiscal_obligations',
] as $table) {
    echo $table.': '.(Schema::hasTable($table) ? 'OK' : 'MISSING').PHP_EOL;
}

echo 'count tax_types: '.MunicipalTaxType::query()->count().PHP_EOL;
