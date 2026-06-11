<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préparation schéma MAMI Taxi V2 — colonnes nullable, sans impact V1.
 * Voir docs/MAMI_TAXI_V2.md
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->string('booking_type')->default('immediate')->after('status');
            $table->timestamp('scheduled_at')->nullable()->after('booking_type');
            $table->timestamp('activated_at')->nullable()->after('scheduled_at');
            $table->decimal('suggested_price', 10, 2)->nullable()->after('estimated_price');
            $table->decimal('proposed_price', 10, 2)->nullable()->after('suggested_price');
            $table->decimal('agreed_price', 10, 2)->nullable()->after('proposed_price');
            $table->string('payment_method')->nullable()->after('agreed_price');
            $table->string('balance_payment_method')->nullable()->after('payment_method');
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('balance_payment_method');
            $table->string('deposit_status')->nullable()->after('deposit_amount');
            $table->decimal('distance_km', 8, 3)->nullable()->after('deposit_status');
            $table->unsignedSmallInteger('duration_minutes')->nullable()->after('distance_km');
            $table->decimal('search_radius_km', 5, 2)->nullable()->after('duration_minutes');
            $table->timestamp('dispatch_started_at')->nullable()->after('search_radius_km');
            $table->timestamp('dispatch_expires_at')->nullable()->after('dispatch_started_at');
            $table->timestamp('accepted_at')->nullable()->after('dispatch_expires_at');
            $table->timestamp('cancelled_at')->nullable()->after('accepted_at');
            $table->string('cancelled_by_role')->nullable()->after('cancelled_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_by_role');
            $table->timestamp('no_show_detected_at')->nullable()->after('cancellation_reason');
            $table->string('no_show_reported_by')->nullable()->after('no_show_detected_at');

            $table->index(['scheduled_at', 'status']);
            $table->index(['dispatch_expires_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('rides', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at', 'status']);
            $table->dropIndex(['dispatch_expires_at', 'status']);

            $table->dropColumn([
                'booking_type',
                'scheduled_at',
                'activated_at',
                'suggested_price',
                'proposed_price',
                'agreed_price',
                'payment_method',
                'balance_payment_method',
                'deposit_amount',
                'deposit_status',
                'distance_km',
                'duration_minutes',
                'search_radius_km',
                'dispatch_started_at',
                'dispatch_expires_at',
                'accepted_at',
                'cancelled_at',
                'cancelled_by_role',
                'cancellation_reason',
                'no_show_detected_at',
                'no_show_reported_by',
            ]);
        });
    }
};
