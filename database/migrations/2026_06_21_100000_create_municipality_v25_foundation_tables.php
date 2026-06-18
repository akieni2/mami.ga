<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_operator_qrcodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->constrained('economic_operators')->restrictOnDelete();
            $table->uuid('qr_uuid')->unique();
            $table->string('qr_value', 60)->comment('Libellé affiché (ex. OWE-COM-000001), non encodé dans le QR');
            $table->timestamp('generated_at');
            $table->timestamp('printed_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['operator_id', 'is_active']);
        });

        Schema::create('field_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->nullable()->constrained('economic_operators')->restrictOnDelete();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->string('visit_type', 30);
            $table->date('visit_date');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['operator_id', 'visit_date']);
            $table->index(['agent_id', 'visit_date']);
        });

        Schema::create('municipal_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('operator_id')->constrained('economic_operators')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->string('payment_period', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['operator_id', 'status']);
            $table->index(['created_at']);
        });

        Schema::create('municipal_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('municipal_payments')->cascadeOnDelete();
            $table->string('receipt_number', 30)->unique();
            $table->string('receipt_qr_value', 255)->unique();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->index('generated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_receipts');
        Schema::dropIfExists('municipal_payments');
        Schema::dropIfExists('field_visits');
        Schema::dropIfExists('economic_operator_qrcodes');
    }
};
