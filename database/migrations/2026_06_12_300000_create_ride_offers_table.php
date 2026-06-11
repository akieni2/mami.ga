<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 — offres dispatch envoyées aux chauffeurs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->decimal('offered_price', 10, 2);
            $table->decimal('counter_price', 10, 2)->nullable();
            $table->decimal('distance_to_pickup_km', 8, 3);
            $table->decimal('dispatch_score', 8, 4)->nullable();
            $table->string('radius_wave', 16);
            $table->timestamp('expires_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['ride_id', 'driver_id']);
            $table->index(['ride_id', 'status']);
            $table->index(['driver_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_offers');
    }
};
