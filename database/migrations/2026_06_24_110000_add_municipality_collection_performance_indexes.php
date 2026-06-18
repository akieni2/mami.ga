<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipal_payments', function (Blueprint $table): void {
            $table->index(['status', 'collected_at'], 'municipal_payments_status_collected_idx');
        });

        Schema::table('economic_operators', function (Blueprint $table): void {
            $table->index('sector_id', 'economic_operators_sector_idx');
        });
    }

    public function down(): void
    {
        Schema::table('municipal_payments', function (Blueprint $table): void {
            $table->dropIndex('municipal_payments_status_collected_idx');
        });

        Schema::table('economic_operators', function (Blueprint $table): void {
            $table->dropIndex('economic_operators_sector_idx');
        });
    }
};
