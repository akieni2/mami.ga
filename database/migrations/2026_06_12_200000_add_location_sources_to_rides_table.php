<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P2B — traçabilité mode de saisie départ / destination.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->string('pickup_source', 16)->default('text')->after('destination_label');
            $table->string('destination_source', 16)->default('text')->after('pickup_source');
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropColumn(['pickup_source', 'destination_source']);
        });
    }
};
