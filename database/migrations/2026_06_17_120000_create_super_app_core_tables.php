<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('module', 30)->default('core');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 100);
            $table->string('module', 30)->default('core');
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('source', 20)->default('text');
            $table->string('commune', 100)->nullable();
            $table->string('quartier', 100)->nullable();
            $table->string('plus_code', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('locatable_type', 80);
            $table->unsignedBigInteger('locatable_id');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('accuracy_meters')->nullable();
            $table->unsignedSmallInteger('heading')->nullable();
            $table->decimal('speed_kmh', 5, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->string('context', 30)->nullable();
            $table->timestamps();

            $table->index(['locatable_type', 'locatable_id', 'recorded_at'], 'locations_locatable_recorded_idx');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->string('rateable_type', 80);
            $table->unsignedBigInteger('rateable_id');
            $table->unsignedTinyInteger('score');
            $table->text('comment')->nullable();
            $table->string('module', 30);
            $table->string('context', 30)->nullable();
            $table->timestamps();

            $table->unique(
                ['rater_id', 'rateable_type', 'rateable_id', 'context'],
                'ratings_unique_per_context'
            );
            $table->index(['rateable_type', 'rateable_id']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type', 80);
            $table->unsignedBigInteger('attachable_id');
            $table->string('disk', 20)->default('local');
            $table->string('path', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('purpose', 30)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payable_type', 80);
            $table->unsignedBigInteger('payable_id');
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('XAF');
            $table->string('method', 30)->default('cash');
            $table->string('status', 20)->default('pending');
            $table->string('external_reference', 100)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['payer_id', 'status']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('XAF');
            $table->string('status', 20)->default('pending');
            $table->string('provider', 30)->nullable();
            $table->string('provider_reference', 100)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'type']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action', 50);
            $table->string('module', 30)->default('core');
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['module', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
