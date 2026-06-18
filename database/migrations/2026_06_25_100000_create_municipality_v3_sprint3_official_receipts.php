<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('municipal_receipts', function (Blueprint $table): void {
            $table->uuid('verification_token')->nullable()->unique()->after('receipt_qr_value');
            $table->string('document_hash', 64)->nullable()->after('verification_token');
            $table->timestamp('signed_at')->nullable()->after('document_hash');
            $table->string('status', 20)->default('valid')->after('signed_at');
            $table->timestamp('annulled_at')->nullable()->after('status');
            $table->foreignId('annulled_by')->nullable()->after('annulled_at')
                ->constrained('users')->nullOnDelete();
            $table->text('annulled_reason')->nullable()->after('annulled_by');
            $table->timestamp('refunded_at')->nullable()->after('annulled_reason');
            $table->foreignId('refunded_by')->nullable()->after('refunded_at')
                ->constrained('users')->nullOnDelete();
            $table->unsignedInteger('reprint_count')->default(0)->after('refunded_by');

            $table->index('status');
            $table->index('document_hash');
        });

        Schema::create('municipal_receipt_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('municipal_receipt_id')->constrained('municipal_receipts')->cascadeOnDelete();
            $table->string('format', 30);
            $table->unsignedInteger('version')->default(1);
            $table->string('storage_path');
            $table->string('disk', 30)->default('local');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['municipal_receipt_id', 'format', 'version'], 'receipt_doc_format_version_unique');
            $table->index(['municipal_receipt_id', 'format']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipal_receipt_documents');

        Schema::table('municipal_receipts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('refunded_by');
            $table->dropConstrainedForeignId('annulled_by');
            $table->dropColumn([
                'verification_token', 'document_hash', 'signed_at', 'status',
                'annulled_at', 'annulled_reason', 'refunded_at', 'reprint_count',
            ]);
        });
    }
};
