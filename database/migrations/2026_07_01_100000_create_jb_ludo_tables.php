<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_player_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('pseudo', 40);
            $table->string('photo_path', 255)->nullable();
            $table->string('city', 80)->nullable();
            $table->string('country', 80)->default('Gabon');
            $table->string('phone', 30)->unique();
            $table->string('level', 20)->default('intermediate');
            $table->string('club', 120)->nullable();
            $table->integer('points')->default(0);
            $table->unsignedInteger('games_played')->default(0);
            $table->unsignedInteger('games_won')->default(0);
            $table->unsignedInteger('games_drawn')->default(0);
            $table->unsignedInteger('games_lost')->default(0);
            $table->unsignedInteger('resign_count')->default(0);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_suspended')->default(false);
            $table->timestamp('suspended_at')->nullable();
            $table->string('suspension_reason', 255)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['points', 'id']);
            $table->index('is_online');
        });

        Schema::create('jb_matches', function (Blueprint $table): void {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->string('mode', 20);
            $table->string('status', 20)->default('waiting');
            $table->foreignId('white_player_id')->nullable()->constrained('jb_player_profiles')->nullOnDelete();
            $table->foreignId('black_player_id')->nullable()->constrained('jb_player_profiles')->nullOnDelete();
            $table->string('turn_color', 10)->default('white');
            $table->json('board_state')->nullable();
            $table->unsignedInteger('clock_seconds')->default(600);
            $table->unsignedInteger('white_time_left')->default(600);
            $table->unsignedInteger('black_time_left')->default(600);
            $table->timestamp('turn_started_at')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->foreignId('disconnected_player_id')->nullable()->constrained('jb_player_profiles')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('jb_player_profiles')->nullOnDelete();
            $table->string('result', 20)->nullable();
            $table->string('result_reason', 40)->nullable();
            $table->unsignedInteger('move_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'mode']);
            $table->index(['white_player_id', 'status']);
            $table->index(['black_player_id', 'status']);
        });

        Schema::create('jb_match_moves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('match_id')->constrained('jb_matches')->cascadeOnDelete();
            $table->unsignedInteger('server_seq');
            $table->foreignId('player_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->string('color', 10);
            $table->json('path');
            $table->json('captures')->nullable();
            $table->boolean('became_king')->default(false);
            $table->json('board_after')->nullable();
            $table->timestamp('played_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['match_id', 'server_seq']);
        });

        Schema::create('jb_rating_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('jb_matches')->nullOnDelete();
            $table->string('event_type', 30);
            $table->integer('delta');
            $table->integer('points_after');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['player_id', 'created_at']);
        });

        Schema::create('jb_friend_invites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('from_player_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->foreignId('to_player_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained('jb_matches')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['to_player_id', 'status']);
        });

        Schema::create('jb_matchmaking_queue', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('player_id')->unique()->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->string('level', 20);
            $table->integer('points')->default(0);
            $table->timestamp('queued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jb_matchmaking_queue');
        Schema::dropIfExists('jb_friend_invites');
        Schema::dropIfExists('jb_rating_events');
        Schema::dropIfExists('jb_match_moves');
        Schema::dropIfExists('jb_matches');
        Schema::dropIfExists('jb_player_profiles');
    }
};
