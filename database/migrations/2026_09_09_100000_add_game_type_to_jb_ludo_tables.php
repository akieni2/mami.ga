<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jb_matches', function (Blueprint $table): void {
            $table->string('game_type', 20)->default('damier')->after('reference');
            $table->json('ludo_player_ids')->nullable()->after('black_player_id');
            $table->index(['game_type', 'status'], 'jb_matches_game_status_idx');
        });

        Schema::table('jb_championships', function (Blueprint $table): void {
            $table->string('game_type', 20)->default('damier')->after('created_by');
            $table->index(['game_type', 'status'], 'jb_championships_game_status_idx');
        });

        Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
            $table->string('game_type', 20)->default('damier')->after('player_id');
            $table->dropUnique(['player_id']);
            $table->unique(['game_type', 'player_id'], 'jb_queue_game_player_unique');
            $table->index(['game_type', 'queued_at'], 'jb_queue_game_queued_idx');
        });
    }

    public function down(): void
    {
        Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
            $table->dropIndex('jb_queue_game_queued_idx');
            $table->dropUnique('jb_queue_game_player_unique');
            $table->unique('player_id');
            $table->dropColumn('game_type');
        });

        Schema::table('jb_championships', function (Blueprint $table): void {
            $table->dropIndex('jb_championships_game_status_idx');
            $table->dropColumn('game_type');
        });

        Schema::table('jb_matches', function (Blueprint $table): void {
            $table->dropIndex('jb_matches_game_status_idx');
            $table->dropColumn(['game_type', 'ludo_player_ids']);
        });
    }
};
