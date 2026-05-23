<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('license_number')->unique();
            $table->boolean('is_available')->default(false);
            $table->string('status')->default('offline');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('rating', 3, 2)->default(5.00);
            $table->timestamps();

            $table->index(['is_available', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
