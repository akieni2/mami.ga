<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fiscal_obligations', function (Blueprint $table): void {
            $table->string('obligation_type', 20)->default('tax')->after('tax_rate_id');
            $table->index(['operator_id', 'obligation_type', 'status'], 'fiscal_obligations_op_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fiscal_obligations', function (Blueprint $table): void {
            $table->dropIndex('fiscal_obligations_op_type_status_idx');
            $table->dropColumn('obligation_type');
        });
    }
};
