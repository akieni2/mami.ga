<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });

        Schema::table('economic_operator_qrcodes', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });

        Schema::table('economic_operator_tax_status', function (Blueprint $table): void {
            $table->dropForeign(['economic_operator_id']);
            $table->foreign('economic_operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('economic_operator_tax_status', function (Blueprint $table): void {
            $table->dropForeign(['economic_operator_id']);
            $table->foreign('economic_operator_id')
                ->references('id')
                ->on('economic_operators')
                ->cascadeOnDelete();
        });

        Schema::table('economic_operator_qrcodes', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->cascadeOnDelete();
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->cascadeOnDelete();
        });
    }
};
