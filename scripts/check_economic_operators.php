<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use App\Modules\Municipality\Models\EconomicZone;

echo "=== Economic Operators — état des tables ===\n\n";

foreach ([
    'economic_operator_categories',
    'economic_zones',
    'economic_operators',
    'economic_operator_qrcodes',
    'economic_operator_tax_status',
    'operator_tax_assignments',
] as $table) {
    echo $table.': '.(Schema::hasTable($table) ? 'OK' : 'MISSING').PHP_EOL;
}

echo PHP_EOL.'Counts:'.PHP_EOL;
echo 'economic_zones: '.(Schema::hasTable('economic_zones') ? EconomicZone::query()->count() : 'N/A').PHP_EOL;
echo 'economic_operators: '.(Schema::hasTable('economic_operators') ? EconomicOperator::query()->count() : 'N/A').PHP_EOL;
echo 'economic_operator_qrcodes: '.(Schema::hasTable('economic_operator_qrcodes') ? EconomicOperatorQrcode::query()->count() : 'N/A').PHP_EOL;
echo 'operator_tax_assignments: '.(Schema::hasTable('operator_tax_assignments') ? \App\Modules\Municipality\Models\OperatorTaxAssignment::query()->count() : 'N/A').PHP_EOL;

if (Schema::hasTable('economic_operators')) {
    $last = EconomicOperator::query()->orderByDesc('id')->value('public_id');
    echo 'dernier public_id: '.($last ?? 'aucun').PHP_EOL;
}
