<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email');
            $table->string('national_id_number');
            $table->string('driving_license_number');
            $table->string('vehicle_brand');
            $table->string('vehicle_model');
            $table->string('vehicle_color');
            $table->unsignedSmallInteger('vehicle_year');
            $table->string('plate_number');
            $table->string('vehicle_type');
            $table->string('driver_photo_path');
            $table->string('license_photo_path');
            $table->string('vehicle_photo_path');
            $table->string('status')->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
            $table->index('driving_license_number');
            $table->index('plate_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_applications');
    }
};
