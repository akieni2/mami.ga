<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('economic_operators', function (Blueprint $table): void {
            $table->index('commercial_name', 'economic_operators_commercial_name_index');
            $table->index('responsible_name', 'economic_operators_responsible_name_index');
            $table->index('phone', 'economic_operators_phone_index');
            $table->index('created_at', 'economic_operators_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('economic_operators', function (Blueprint $table): void {
            $table->dropIndex('economic_operators_commercial_name_index');
            $table->dropIndex('economic_operators_responsible_name_index');
            $table->dropIndex('economic_operators_phone_index');
            $table->dropIndex('economic_operators_created_at_index');
        });
    }
};
