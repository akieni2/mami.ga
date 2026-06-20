<?php

use App\Modules\Municipality\Models\EconomicOperator;
use App\Modules\Municipality\Models\EconomicOperatorQrcode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            EconomicOperator::query()
                ->where('public_id', 'like', 'OWE-COM-%')
                ->orderBy('id')
                ->each(function (EconomicOperator $operator): void {
                    if (! preg_match('/^OWE-COM-(\d+)$/', $operator->public_id, $matches)) {
                        return;
                    }

                    $oldPublicId = $operator->public_id;
                    $padded = sprintf('OWE-COM-%08d', (int) $matches[1]);

                    if ($padded === $oldPublicId) {
                        return;
                    }

                    $operator->update(['public_id' => $padded]);

                    EconomicOperatorQrcode::query()
                        ->where('operator_id', $operator->id)
                        ->where('qr_value', $oldPublicId)
                        ->update(['qr_value' => $padded]);
                });
        });
    }

    public function down(): void
    {
        // Non réversible sans perte d'unicité si de nouveaux IDs 8 chiffres existent.
    }
};
