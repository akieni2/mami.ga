<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        DB::table('financial_missions')->where('status', 'authorized')->update([
            'workflow_status' => 'approved',
            'approved_at' => DB::raw('authorized_at'),
        ]);

        DB::table('financial_missions')->where('status', 'closed')->update([
            'workflow_status' => 'closed',
        ]);

        Schema::create('financial_mission_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('financial_mission_id')->constrained('financial_missions')->cascadeOnDelete();
            $table->string('action', 30);
            $table->foreignId('performed_by')->constrained('users')->cascadeOnDelete();
            $table->text('comments')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['financial_mission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_mission_approvals');

        Schema::table('financial_missions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('controller_id');
            $table->dropConstrainedForeignId('daf_id');
            $table->dropIndex(['workflow_status']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['approved_at']);
            $table->dropColumn([
                'workflow_status',
                'submitted_at',
                'controller_reviewed_at',
                'daf_reviewed_at',
                'approved_at',
                'rejected_at',
                'rejection_reason',
            ]);
        });
    }
};
