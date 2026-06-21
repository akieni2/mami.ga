<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 3.2.6 — SessionOpen / SessionClose : visites sans commerce (operator_id null).
 * Compatible MySQL 8 : ALTER COLUMN + re-création FK restrictOnDelete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->unsignedBigInteger('operator_id')->nullable()->change();
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('field_visits', function (Blueprint $table): void {
            $table->dropForeign(['operator_id']);
        });

        Schema::table('field_visits', function (Blueprint $table): void {
            $table->unsignedBigInteger('operator_id')->nullable(false)->change();
            $table->foreign('operator_id')
                ->references('id')
                ->on('economic_operators')
                ->restrictOnDelete();
        });
    }
};
