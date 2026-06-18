<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipal_tax_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('municipal_tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('municipal_tax_types')->restrictOnDelete();
            $table->decimal('amount_xaf', 14, 2);
            $table->string('billing_period', 20);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tax_type_id', 'is_active']);
            $table->index(['tax_type_id', 'valid_from', 'valid_to']);
        });

        Schema::create('municipal_collection_targets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tax_type_id')->constrained('municipal_tax_types')->restrictOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('target_amount_xaf', 16, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tax_type_id', 'fiscal_year']);
        });

        Schema::create('operator_tax_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->constrained('economic_operators')->restrictOnDelete();
            $table->foreignId('tax_type_id')->constrained('municipal_tax_types')->restrictOnDelete();
            $table->timestamp('assigned_at');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'is_active']);
            $table->index(['tax_type_id', 'is_active']);
        });

        Schema::create('fiscal_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->constrained('economic_operators')->restrictOnDelete();
            $table->foreignId('tax_type_id')->constrained('municipal_tax_types')->restrictOnDelete();
            $table->foreignId('tax_rate_id')->constrained('municipal_tax_rates')->restrictOnDelete();
            $table->string('reference', 30)->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('amount_due', 14, 2);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('balance_due', 14, 2);
            $table->string('status', 20)->default('open');
            $table->timestamp('generated_at');
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['operator_id', 'tax_type_id', 'period_start', 'period_end'], 'fiscal_obligations_period_unique');
            $table->index(['operator_id', 'status']);
            $table->index(['tax_type_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_obligations');
        Schema::dropIfExists('operator_tax_assignments');
        Schema::dropIfExists('municipal_collection_targets');
        Schema::dropIfExists('municipal_tax_rates');
        Schema::dropIfExists('municipal_tax_types');
    }
};
