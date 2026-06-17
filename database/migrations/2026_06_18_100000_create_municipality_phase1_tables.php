<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipal_territories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 20)->unique();
            $table->decimal('bounds_sw_lat', 10, 7)->nullable();
            $table->decimal('bounds_sw_lng', 10, 7)->nullable();
            $table->decimal('bounds_ne_lat', 10, 7)->nullable();
            $table->decimal('bounds_ne_lng', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('municipal_sectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('territory_id')->constrained('municipal_territories')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 50);
            $table->string('code', 20)->nullable();
            $table->string('sector_type', 20)->default('quartier');
            $table->foreignId('parent_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->decimal('center_latitude', 10, 7)->nullable();
            $table->decimal('center_longitude', 10, 7)->nullable();
            $table->json('polygon_geojson')->nullable();
            $table->timestamps();

            $table->unique(['territory_id', 'slug']);
            $table->index(['territory_id', 'sector_type']);
        });

        Schema::create('municipality_reports', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('citizen_id')->constrained('users')->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('title', 255);
            $table->text('description');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address', 500)->nullable();
            $table->foreignId('territory_id')->constrained('municipal_territories');
            $table->foreignId('sector_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->foreignId('operational_zone_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->string('status', 20)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index(['latitude', 'longitude']);
            $table->index(['sector_id', 'status']);
            $table->index(['citizen_id', 'created_at']);
        });

        Schema::create('municipality_report_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipality_report_id')->constrained('municipality_reports')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['municipality_report_id', 'created_at'], 'muni_report_updates_report_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipality_report_updates');
        Schema::dropIfExists('municipality_reports');
        Schema::dropIfExists('municipal_sectors');
        Schema::dropIfExists('municipal_territories');
    }
};
