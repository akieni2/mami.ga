<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_missions', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('title', 200);
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('operational_zone_id')->nullable()->constrained('municipal_sectors')->nullOnDelete();
            $table->date('valid_from');
            $table->date('valid_until');
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('authorized_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
            $table->index(['valid_from', 'valid_until']);
        });

        Schema::create('municipal_finance_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 60);
            $table->string('subject_type', 60);
            $table->unsignedBigInteger('subject_id');
            $table->foreignId('financial_mission_id')->nullable()->constrained('financial_missions')->nullOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index('occurred_at');
        });

        Schema::create('municipal_treasury_remittances', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->decimal('amount_xaf', 14, 2);
            $table->string('status', 20)->default('draft');
            $table->foreignId('prepared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('remitted_at')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->foreignId('financial_mission_id')
                ->nullable()
                ->after('agent_id')
                ->constrained('financial_missions')
                ->nullOnDelete();
            $table->foreignId('admin_closed_by')
                ->nullable()
                ->after('closed_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('closure_type', 20)->nullable()->after('admin_closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('financial_mission_id');
            $table->dropConstrainedForeignId('admin_closed_by');
            $table->dropColumn('closure_type');
        });

        Schema::dropIfExists('municipal_treasury_remittances');
        Schema::dropIfExists('municipal_finance_journal_entries');
        Schema::dropIfExists('financial_missions');
    }
};
