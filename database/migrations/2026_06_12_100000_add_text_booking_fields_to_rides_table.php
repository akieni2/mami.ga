<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2A — réservation text-first : labels texte, coords GPS facultatives.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->string('pickup_label', 255)->nullable()->after('driver_id');
            $table->string('destination_label', 255)->nullable()->after('pickup_label');
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->change();
            $table->decimal('pickup_longitude', 10, 7)->nullable()->change();
            $table->decimal('destination_latitude', 10, 7)->nullable()->change();
            $table->decimal('destination_longitude', 10, 7)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['pickup_label', 'destination_label']);
        });

        Schema::table('rides', function (Blueprint $table) {
            $table->decimal('pickup_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('pickup_longitude', 10, 7)->nullable(false)->change();
            $table->decimal('destination_latitude', 10, 7)->nullable(false)->change();
            $table->decimal('destination_longitude', 10, 7)->nullable(false)->change();
        });
    }
};
