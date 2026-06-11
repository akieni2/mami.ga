<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P3 — audit des vagues de dispatch.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ride_dispatch_waves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->constrained()->cascadeOnDelete();
            $table->decimal('radius_min_km', 8, 2);
            $table->decimal('radius_max_km', 8, 2);
            $table->unsignedInteger('drivers_notified')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['ride_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ride_dispatch_waves');
    }
};
