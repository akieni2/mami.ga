<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 4.1 — workflow visa financier.
 *
 * DOIT s'exécuter après :
 * 2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php
 *
 * Idempotent : safe sur base vierge (post-4.0), prod 4.0 seule, ou rejeu après échec.
 */
return new class extends Migration
{
    private const PHANTOM_MIGRATION = '2026_06_16_200000_add_workflow_to_financial_missions_sprint41';

    private const CREATE_MIGRATION = '2026_06_28_100000_create_municipality_sprint4_financial_governance_tables';

    public function up(): void
    {
        $this->reconcileMisorderedMigrationRecord();

        if (! Schema::hasTable('financial_missions')) {
            throw new RuntimeException(
                'La table financial_missions est absente. Exécutez d\'abord la migration '
                .self::CREATE_MIGRATION.'.'
            );
        }

        $this->ensureFinancialMissionsWorkflowColumns();
        $this->migrateExistingMissionStatuses();
        $this->ensureFinancialMissionApprovalsTable();
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_mission_approvals');

        if (! Schema::hasTable('financial_missions')) {
            return;
        }

        Schema::table('financial_missions', function (Blueprint $table): void {
            if (Schema::hasColumn('financial_missions', 'controller_id')) {
                $table->dropConstrainedForeignId('controller_id');
            }
            if (Schema::hasColumn('financial_missions', 'daf_id')) {
                $table->dropConstrainedForeignId('daf_id');
            }

            $columnsToDrop = array_filter([
                Schema::hasColumn('financial_missions', 'workflow_status') ? 'workflow_status' : null,
                Schema::hasColumn('financial_missions', 'submitted_at') ? 'submitted_at' : null,
                Schema::hasColumn('financial_missions', 'controller_reviewed_at') ? 'controller_reviewed_at' : null,
                Schema::hasColumn('financial_missions', 'daf_reviewed_at') ? 'daf_reviewed_at' : null,
                Schema::hasColumn('financial_missions', 'approved_at') ? 'approved_at' : null,
                Schema::hasColumn('financial_missions', 'rejected_at') ? 'rejected_at' : null,
                Schema::hasColumn('financial_missions', 'rejection_reason') ? 'rejection_reason' : null,
            ]);

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    private function reconcileMisorderedMigrationRecord(): void
    {
        if (! Schema::hasTable('migrations')) {
            return;
        }

        $phantomRecorded = DB::table('migrations')
            ->where('migration', self::PHANTOM_MIGRATION)
            ->exists();

        if (! $phantomRecorded) {
            return;
        }

        $workflowApplied = Schema::hasTable('financial_missions')
            && Schema::hasColumn('financial_missions', 'workflow_status');

        if (! $workflowApplied) {
            DB::table('migrations')
                ->where('migration', self::PHANTOM_MIGRATION)
                ->delete();
        }
    }

    private function ensureFinancialMissionsWorkflowColumns(): void
    {
        if (Schema::hasColumn('financial_missions', 'workflow_status')) {
            return;
        }

        Schema::table('financial_missions', function (Blueprint $table): void {
            $table->string('workflow_status', 30)->default('draft')->after('status');
            $table->timestamp('submitted_at')->nullable()->after('workflow_status');
            $table->timestamp('controller_reviewed_at')->nullable()->after('submitted_at');
            $table->timestamp('daf_reviewed_at')->nullable()->after('controller_reviewed_at');
            $table->timestamp('approved_at')->nullable()->after('daf_reviewed_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_at');
            $table->foreignId('controller_id')->nullable()->after('rejected_at')->constrained('users')->nullOnDelete();
            $table->foreignId('daf_id')->nullable()->after('controller_id')->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable()->after('daf_id');

            $table->index('workflow_status');
            $table->index('submitted_at');
            $table->index('approved_at');
        });
    }

    private function migrateExistingMissionStatuses(): void
    {
        if (! Schema::hasColumn('financial_missions', 'workflow_status')) {
            return;
        }

        DB::table('financial_missions')
            ->where('status', 'authorized')
            ->where(function ($query): void {
                $query->whereNull('workflow_status')
                    ->orWhere('workflow_status', 'draft');
            })
            ->update([
                'workflow_status' => 'approved',
                'approved_at' => DB::raw('COALESCE(approved_at, authorized_at)'),
            ]);

        DB::table('financial_missions')
            ->where('status', 'closed')
            ->where(function ($query): void {
                $query->whereNull('workflow_status')
                    ->orWhere('workflow_status', 'draft');
            })
            ->update(['workflow_status' => 'closed']);
    }

    private function ensureFinancialMissionApprovalsTable(): void
    {
        if (! Schema::hasTable('financial_mission_approvals')) {
            Schema::create('financial_mission_approvals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('financial_mission_id')->constrained('financial_missions')->cascadeOnDelete();
                $table->string('action', 30);
                $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
                $table->text('comments')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['financial_mission_id', 'created_at'], 'fma_mission_created_idx');
            });

            return;
        }

        if ($this->indexExists('financial_mission_approvals', 'fma_mission_created_idx')) {
            return;
        }

        Schema::table('financial_mission_approvals', function (Blueprint $table): void {
            $table->index(['financial_mission_id', 'created_at'], 'fma_mission_created_idx');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => ($index['name'] ?? '') === $indexName);
    }
};
