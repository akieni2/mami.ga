<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_player_profiles', function (Blueprint $table): void {
            $table->string('first_name', 80)->nullable()->after('user_id');
            $table->string('last_name', 80)->nullable()->after('first_name');
            $table->string('neighborhood', 120)->nullable()->after('city');

            $table->index(['country', 'city', 'neighborhood'], 'jb_profiles_location_idx');
        });

        Schema::table('jb_championships', function (Blueprint $table): void {
            $table->string('scope', 30)->default('national')->after('game_type');
            $table->string('country', 80)->default('Gabon')->after('scope');
            $table->string('city', 80)->nullable()->after('country');
            $table->string('neighborhood', 120)->nullable()->after('city');
            $table->string('prize_title', 160)->nullable()->after('max_participants');
            $table->unsignedBigInteger('prize_amount')->nullable()->after('prize_title');
            $table->string('prize_currency', 10)->default('XAF')->after('prize_amount');
            $table->text('prize_description')->nullable()->after('prize_currency');

            $table->index(['game_type', 'scope', 'country', 'city'], 'jb_championships_scope_idx');
        });

        Schema::create('jb_teams', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->string('country', 80)->default('Gabon');
            $table->string('city', 80);
            $table->string('neighborhood', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['country', 'city', 'neighborhood']);
        });

        Schema::create('jb_team_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('team_id')->constrained('jb_teams')->cascadeOnDelete();
            $table->foreignId('player_profile_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->string('role', 30)->default('player');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'player_profile_id', 'role'], 'jb_team_member_role_unique');
            $table->index(['player_profile_id', 'left_at']);
        });

        Schema::create('jb_transfer_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_profile_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->foreignId('from_team_id')->nullable()->constrained('jb_teams')->nullOnDelete();
            $table->foreignId('to_team_id')->constrained('jb_teams')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 40)->default('pending_commission');
            $table->text('reason')->nullable();
            $table->text('commission_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'to_team_id']);
            $table->index(['player_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_transfer_requests');
        Schema::dropIfExists('jb_team_members');
        Schema::dropIfExists('jb_teams');

        Schema::table('jb_championships', function (Blueprint $table): void {
            $table->dropIndex('jb_championships_scope_idx');
            $table->dropColumn([
                'scope',
                'country',
                'city',
                'neighborhood',
                'prize_title',
                'prize_amount',
                'prize_currency',
                'prize_description',
            ]);
        });

        Schema::table('jb_player_profiles', function (Blueprint $table): void {
            $table->dropIndex('jb_profiles_location_idx');
            $table->dropColumn(['first_name', 'last_name', 'neighborhood']);
        });
    }
};
