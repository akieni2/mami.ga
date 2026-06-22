<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipal_treasury_remittances', function (Blueprint $table): void {
            $table->string('slip_number', 40)->nullable()->after('notes');
            $table->string('bank_name', 120)->nullable()->after('slip_number');
            $table->string('deposit_reference', 80)->nullable()->after('bank_name');
            $table->timestamp('deposited_at')->nullable()->after('deposit_reference');
            $table->string('treasury_receipt_ref', 80)->nullable()->after('deposited_at');
            $table->timestamp('confirmed_at')->nullable()->after('treasury_receipt_ref');
            $table->text('rejection_reason')->nullable()->after('confirmed_at');

            $table->foreignId('controlled_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('controlled_at')->nullable()->after('controlled_by');
            $table->foreignId('daf_validated_by')->nullable()->after('controlled_at')->constrained('users')->nullOnDelete();
            $table->timestamp('daf_validated_at')->nullable()->after('daf_validated_by');
            $table->foreignId('receveur_validated_by')->nullable()->after('daf_validated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('receveur_validated_at')->nullable()->after('receveur_validated_by');
            $table->foreignId('deposited_by')->nullable()->after('receveur_validated_at')->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->after('deposited_by')->constrained('users')->nullOnDelete();

            $table->string('accounting_batch_id', 64)->nullable()->after('confirmed_by');
            $table->string('accounting_export_status', 20)->default('pending')->after('accounting_batch_id');
            $table->timestamp('accounting_posted_at')->nullable()->after('accounting_export_status');

            $table->decimal('reconciled_amount_xaf', 14, 2)->nullable()->after('amount_xaf');
            $table->unsignedInteger('payment_count')->default(0)->after('reconciled_amount_xaf');
            $table->unsignedInteger('cash_session_count')->default(0)->after('payment_count');
        });

        DB::table('municipal_treasury_remittances')->where('status', 'pending')->update(['status' => 'controlled']);
        DB::table('municipal_treasury_remittances')->where('status', 'remitted')->update(['status' => 'confirmed']);

        Schema::create('municipal_treasury_remittance_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remittance_id')->constrained('municipal_treasury_remittances')->cascadeOnDelete();
            $table->foreignId('municipal_payment_id')->constrained('municipal_payments')->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained('cash_sessions')->nullOnDelete();
            $table->decimal('amount_allocated', 14, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique('municipal_payment_id', 'mtr_payment_unique');
            $table->index(['remittance_id', 'municipal_payment_id'], 'mtr_remittance_payment_idx');
        });

        Schema::create('municipal_treasury_remittance_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('remittance_id')->constrained('municipal_treasury_remittances')->cascadeOnDelete();
            $table->string('action', 40);
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['remittance_id', 'created_at'], 'mtra_remittance_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_treasury_remittance_approvals');
        Schema::dropIfExists('municipal_treasury_remittance_payments');

        DB::table('municipal_treasury_remittances')->where('status', 'controlled')->update(['status' => 'pending']);
        DB::table('municipal_treasury_remittances')->where('status', 'confirmed')->update(['status' => 'remitted']);

        Schema::table('municipal_treasury_remittances', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('controlled_by');
            $table->dropConstrainedForeignId('daf_validated_by');
            $table->dropConstrainedForeignId('receveur_validated_by');
            $table->dropConstrainedForeignId('deposited_by');
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn([
                'slip_number',
                'bank_name',
                'deposit_reference',
                'deposited_at',
                'treasury_receipt_ref',
                'confirmed_at',
                'rejection_reason',
                'controlled_at',
                'daf_validated_at',
                'receveur_validated_at',
                'accounting_batch_id',
                'accounting_export_status',
                'accounting_posted_at',
                'reconciled_amount_xaf',
                'payment_count',
                'cash_session_count',
            ]);
        });
    }
};
