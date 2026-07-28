<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4.2 — workflow reversement Trésor.
 *
 * Idempotent : rejouable après échec MySQL (noms FK > 64 caractères).
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureRemittanceWorkflowColumns();

        DB::table('municipal_treasury_remittances')->where('status', 'pending')->update(['status' => 'controlled']);
        DB::table('municipal_treasury_remittances')->where('status', 'remitted')->update(['status' => 'confirmed']);

        $this->ensureRemittancePaymentsTable();
        $this->ensureRemittanceApprovalsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_treasury_remittance_approvals');
        Schema::dropIfExists('municipal_treasury_remittance_payments');

        DB::table('municipal_treasury_remittances')->where('status', 'controlled')->update(['status' => 'pending']);
        DB::table('municipal_treasury_remittances')->where('status', 'confirmed')->update(['status' => 'remitted']);

        if (! Schema::hasTable('municipal_treasury_remittances')) {
            return;
        }

        Schema::table('municipal_treasury_remittances', function (Blueprint $table): void {
            foreach (['controlled_by', 'daf_validated_by', 'receveur_validated_by', 'deposited_by', 'confirmed_by'] as $column) {
                if (Schema::hasColumn('municipal_treasury_remittances', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }

            $columnsToDrop = array_values(array_filter([
                Schema::hasColumn('municipal_treasury_remittances', 'slip_number') ? 'slip_number' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'bank_name') ? 'bank_name' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'deposit_reference') ? 'deposit_reference' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'deposited_at') ? 'deposited_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'treasury_receipt_ref') ? 'treasury_receipt_ref' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'confirmed_at') ? 'confirmed_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'rejection_reason') ? 'rejection_reason' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'controlled_at') ? 'controlled_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'daf_validated_at') ? 'daf_validated_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'receveur_validated_at') ? 'receveur_validated_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'accounting_batch_id') ? 'accounting_batch_id' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'accounting_export_status') ? 'accounting_export_status' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'accounting_posted_at') ? 'accounting_posted_at' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'reconciled_amount_xaf') ? 'reconciled_amount_xaf' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'payment_count') ? 'payment_count' : null,
                Schema::hasColumn('municipal_treasury_remittances', 'cash_session_count') ? 'cash_session_count' : null,
            ]));

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    private function ensureRemittanceWorkflowColumns(): void
    {
        if (! Schema::hasTable('municipal_treasury_remittances')) {
            throw new RuntimeException('Table municipal_treasury_remittances absente (Sprint 4.0 requis).');
        }

        Schema::table('municipal_treasury_remittances', function (Blueprint $table): void {
            if (! Schema::hasColumn('municipal_treasury_remittances', 'reconciled_amount_xaf')) {
                $table->decimal('reconciled_amount_xaf', 14, 2)->nullable()->after('amount_xaf');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'payment_count')) {
                $table->unsignedInteger('payment_count')->default(0)->after('reconciled_amount_xaf');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'cash_session_count')) {
                $table->unsignedInteger('cash_session_count')->default(0)->after('payment_count');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'slip_number')) {
                $table->string('slip_number', 40)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'bank_name')) {
                $table->string('bank_name', 120)->nullable()->after('slip_number');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'deposit_reference')) {
                $table->string('deposit_reference', 80)->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'deposited_at')) {
                $table->timestamp('deposited_at')->nullable()->after('deposit_reference');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'treasury_receipt_ref')) {
                $table->string('treasury_receipt_ref', 80)->nullable()->after('deposited_at');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('treasury_receipt_ref');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('confirmed_at');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'controlled_by')) {
                $table->unsignedBigInteger('controlled_by')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'controlled_at')) {
                $table->timestamp('controlled_at')->nullable()->after('controlled_by');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'daf_validated_by')) {
                $table->unsignedBigInteger('daf_validated_by')->nullable()->after('controlled_at');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'daf_validated_at')) {
                $table->timestamp('daf_validated_at')->nullable()->after('daf_validated_by');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'receveur_validated_by')) {
                $table->unsignedBigInteger('receveur_validated_by')->nullable()->after('daf_validated_at');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'receveur_validated_at')) {
                $table->timestamp('receveur_validated_at')->nullable()->after('receveur_validated_by');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'deposited_by')) {
                $table->unsignedBigInteger('deposited_by')->nullable()->after('receveur_validated_at');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'confirmed_by')) {
                $table->unsignedBigInteger('confirmed_by')->nullable()->after('deposited_by');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'accounting_batch_id')) {
                $table->string('accounting_batch_id', 64)->nullable()->after('confirmed_by');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'accounting_export_status')) {
                $table->string('accounting_export_status', 20)->default('pending')->after('accounting_batch_id');
            }
            if (! Schema::hasColumn('municipal_treasury_remittances', 'accounting_posted_at')) {
                $table->timestamp('accounting_posted_at')->nullable()->after('accounting_export_status');
            }
        });

        $this->ensureForeignKey('municipal_treasury_remittances', 'controlled_by', 'users', 'mtr_controlled_by_fk', true);
        $this->ensureForeignKey('municipal_treasury_remittances', 'daf_validated_by', 'users', 'mtr_daf_validated_by_fk', true);
        $this->ensureForeignKey('municipal_treasury_remittances', 'receveur_validated_by', 'users', 'mtr_receveur_validated_by_fk', true);
        $this->ensureForeignKey('municipal_treasury_remittances', 'deposited_by', 'users', 'mtr_deposited_by_fk', true);
        $this->ensureForeignKey('municipal_treasury_remittances', 'confirmed_by', 'users', 'mtr_confirmed_by_fk', true);
    }

    private function ensureRemittancePaymentsTable(): void
    {
        if (Schema::hasTable('municipal_treasury_remittance_payments')) {
            $this->ensureForeignKey(
                'municipal_treasury_remittance_payments',
                'remittance_id',
                'municipal_treasury_remittances',
                'mtrp_remittance_fk',
                false,
                'cascade',
            );
            $this->ensureForeignKey(
                'municipal_treasury_remittance_payments',
                'municipal_payment_id',
                'municipal_payments',
                'mtrp_payment_fk',
                false,
                'cascade',
            );
            $this->ensureForeignKey(
                'municipal_treasury_remittance_payments',
                'cash_session_id',
                'cash_sessions',
                'mtrp_cash_session_fk',
                true,
            );

            return;
        }

        Schema::create('municipal_treasury_remittance_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('remittance_id');
            $table->unsignedBigInteger('municipal_payment_id');
            $table->unsignedBigInteger('cash_session_id')->nullable();
            $table->decimal('amount_allocated', 14, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->unique('municipal_payment_id', 'mtr_payment_unique');
            $table->index(['remittance_id', 'municipal_payment_id'], 'mtr_remittance_payment_idx');

            $table->foreign('remittance_id', 'mtrp_remittance_fk')
                ->references('id')->on('municipal_treasury_remittances')->cascadeOnDelete();
            $table->foreign('municipal_payment_id', 'mtrp_payment_fk')
                ->references('id')->on('municipal_payments')->cascadeOnDelete();
            $table->foreign('cash_session_id', 'mtrp_cash_session_fk')
                ->references('id')->on('cash_sessions')->nullOnDelete();
        });
    }

    private function ensureRemittanceApprovalsTable(): void
    {
        if (Schema::hasTable('municipal_treasury_remittance_approvals')) {
            $this->ensureForeignKey(
                'municipal_treasury_remittance_approvals',
                'remittance_id',
                'municipal_treasury_remittances',
                'mtra_remittance_fk',
                false,
                'cascade',
            );
            $this->ensureForeignKey(
                'municipal_treasury_remittance_approvals',
                'performed_by',
                'users',
                'mtra_performed_by_fk',
                false,
                'cascade',
            );

            return;
        }

        Schema::create('municipal_treasury_remittance_approvals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('remittance_id');
            $table->string('action', 40);
            $table->unsignedBigInteger('performed_by');
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['remittance_id', 'created_at'], 'mtra_remittance_created_idx');

            $table->foreign('remittance_id', 'mtra_remittance_fk')
                ->references('id')->on('municipal_treasury_remittances')->cascadeOnDelete();
            $table->foreign('performed_by', 'mtra_performed_by_fk')
                ->references('id')->on('users')->cascadeOnDelete();
        });
    }

    private function ensureForeignKey(
        string $table,
        string $column,
        string $references,
        string $name,
        bool $nullOnDelete,
        string $onDelete = 'set null',
    ): void {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        // Déjà une FK sur cette colonne (nom court ou auto Laravel).
        $exists = DB::selectOne(
            'SELECT CONSTRAINT_NAME AS name
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column],
        );

        if ($exists !== null) {
            return;
        }

        $delete = $nullOnDelete || $onDelete === 'set null' ? 'SET NULL' : 'CASCADE';

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES `%s` (`id`) ON DELETE %s',
            $table,
            $name,
            $column,
            $references,
            $delete,
        ));
    }
};
