<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->foreignId('agent_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_amount_xaf', 14, 2)->default(0);
            $table->decimal('expected_amount_xaf', 14, 2)->default(0);
            $table->decimal('actual_amount_xaf', 14, 2)->nullable();
            $table->string('status', 20)->default('open');
            $table->decimal('opening_latitude', 10, 7)->nullable();
            $table->decimal('opening_longitude', 10, 7)->nullable();
            $table->decimal('closing_latitude', 10, 7)->nullable();
            $table->decimal('closing_longitude', 10, 7)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index('opened_at');
        });

        Schema::table('municipal_payments', function (Blueprint $table): void {
            $table->foreignId('cash_session_id')->nullable()->after('agent_id')
                ->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('core_payment_id')->nullable()->after('cash_session_id')
                ->constrained('payments')->nullOnDelete();
            $table->decimal('latitude', 10, 7)->nullable()->after('status');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('gps_accuracy_m', 8, 2)->nullable()->after('longitude');
            $table->string('device_id', 100)->nullable()->after('gps_accuracy_m');
            $table->text('notes')->nullable()->after('device_id');
            $table->timestamp('collected_at')->nullable()->after('notes');
            $table->uuid('client_operation_id')->nullable()->unique()->after('collected_at');

            $table->index(['cash_session_id', 'status']);
            $table->index(['agent_id', 'collected_at']);
        });

        Schema::create('municipal_payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipal_payment_id')->constrained('municipal_payments')->cascadeOnDelete();
            $table->foreignId('fiscal_obligation_id')->constrained('fiscal_obligations')->restrictOnDelete();
            $table->decimal('amount_allocated', 14, 2);
            $table->timestamps();

            $table->unique(['municipal_payment_id', 'fiscal_obligation_id'], 'mp_alloc_payment_obligation_unique');
            $table->index('fiscal_obligation_id');
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->foreignId('cash_session_id')->nullable()->after('agent_id')
                ->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('municipal_payment_id')->nullable()->after('cash_session_id')
                ->constrained('municipal_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('municipal_payment_id');
            $table->dropConstrainedForeignId('cash_session_id');
        });

        Schema::dropIfExists('municipal_payment_allocations');

        Schema::table('municipal_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cash_session_id');
            $table->dropConstrainedForeignId('core_payment_id');
            $table->dropColumn([
                'latitude', 'longitude', 'gps_accuracy_m', 'device_id',
                'notes', 'collected_at', 'client_operation_id',
            ]);
        });

        Schema::dropIfExists('cash_sessions');
    }
};
