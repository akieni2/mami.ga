<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_operator_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->foreignId('parent_id')->nullable()->constrained('economic_operator_categories')->nullOnDelete();
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('economic_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('territory_id')->constrained('municipal_territories')->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('slug', 50);
            $table->string('zone_kind', 30);
            $table->foreignId('operational_zone_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->foreignId('primary_sector_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->decimal('center_latitude', 10, 7)->nullable();
            $table->decimal('center_longitude', 10, 7)->nullable();
            $table->json('polygon_geojson')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['territory_id', 'zone_kind']);
        });

        Schema::create('economic_operators', function (Blueprint $table): void {
            $table->id();
            $table->string('public_id', 20)->unique();
            $table->foreignId('territory_id')->constrained('municipal_territories')->cascadeOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->foreignId('operational_zone_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->foreignId('economic_zone_id')->nullable()->constrained('economic_zones')->nullOnDelete();
            $table->foreignId('arrondissement_sector_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->foreignId('category_id')->constrained('economic_operator_categories')->restrictOnDelete();
            $table->string('commercial_name', 255);
            $table->string('activity_label', 255);
            $table->string('responsible_name', 150);
            $table->string('phone', 20);
            $table->string('email', 150)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('gps_accuracy_m', 8, 2);
            $table->timestamp('gps_captured_at');
            $table->string('sync_status', 20)->default('synced');
            $table->date('registration_date');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_visit_at')->nullable();
            $table->string('secteur', 100)->nullable();
            $table->string('current_tax_status', 30)->default('a_jour');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['sector_id', 'current_tax_status']);
            $table->index(['economic_zone_id', 'current_tax_status']);
            $table->index(['operational_zone_id']);
            $table->index(['registered_by', 'created_at']);
            $table->index('sync_status');
        });

        Schema::create('economic_operator_tax_status', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('economic_operator_id')->constrained('economic_operators')->restrictOnDelete();
            $table->string('status', 30);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->integer('days_overdue')->nullable();
            $table->decimal('outstanding_amount', 12, 2)->nullable();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['economic_operator_id', 'effective_from'], 'econ_op_tax_status_op_eff_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('economic_operator_tax_status');
        Schema::dropIfExists('economic_operators');
        Schema::dropIfExists('economic_zones');
        Schema::dropIfExists('economic_operator_categories');
    }
};
