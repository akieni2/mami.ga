<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('economic_operator_qrcodes')) {
            return;
        }

        foreach (Schema::getIndexes('economic_operator_qrcodes') as $index) {
            if (! $index['unique']) {
                continue;
            }

            if ($index['columns'] === ['qr_value']) {
                Schema::table('economic_operator_qrcodes', function (Blueprint $table) use ($index): void {
                    $table->dropIndex($index['name']);
                });
                break;
            }
        }

        Schema::table('economic_operator_qrcodes', function (Blueprint $table): void {
            $table->string('qr_value', 60)->comment('Libellé affiché (ex. OWE-COM-000001), non encodé dans le QR')->change();
        });
    }

    public function down(): void
    {
        Schema::table('economic_operator_qrcodes', function (Blueprint $table): void {
            $table->string('qr_value', 40)->change();
            $table->unique('qr_value');
        });
    }
};
