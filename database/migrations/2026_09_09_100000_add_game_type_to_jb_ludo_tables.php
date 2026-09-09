<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('jb_matches', 'game_type')) {
            Schema::table('jb_matches', function (Blueprint $table): void {
                $table->string('game_type', 20)->default('damier')->after('reference');
            });
        }

        if (! Schema::hasColumn('jb_matches', 'ludo_player_ids')) {
            Schema::table('jb_matches', function (Blueprint $table): void {
                $table->json('ludo_player_ids')->nullable()->after('black_player_id');
            });
        }

        if (! $this->indexExists('jb_matches', 'jb_matches_game_status_idx')) {
            Schema::table('jb_matches', function (Blueprint $table): void {
                $table->index(['game_type', 'status'], 'jb_matches_game_status_idx');
            });
        }

        if (! Schema::hasColumn('jb_championships', 'game_type')) {
            Schema::table('jb_championships', function (Blueprint $table): void {
                $table->string('game_type', 20)->default('damier')->after('created_by');
            });
        }

        if (! $this->indexExists('jb_championships', 'jb_championships_game_status_idx')) {
            Schema::table('jb_championships', function (Blueprint $table): void {
                $table->index(['game_type', 'status'], 'jb_championships_game_status_idx');
            });
        }

        if (! Schema::hasColumn('jb_matchmaking_queue', 'game_type')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->string('game_type', 20)->default('damier')->after('player_id');
            });
        }

        if (! $this->indexExists('jb_matchmaking_queue', 'jb_matchmaking_queue_player_id_idx')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->index('player_id', 'jb_matchmaking_queue_player_id_idx');
            });
        }

        if ($this->indexExists('jb_matchmaking_queue', 'jb_matchmaking_queue_player_id_unique')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->dropUnique('jb_matchmaking_queue_player_id_unique');
            });
        }

        if (! $this->indexExists('jb_matchmaking_queue', 'jb_queue_game_player_unique')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->unique(['game_type', 'player_id'], 'jb_queue_game_player_unique');
            });
        }

        if (! $this->indexExists('jb_matchmaking_queue', 'jb_queue_game_queued_idx')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->index(['game_type', 'queued_at'], 'jb_queue_game_queued_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('jb_matchmaking_queue', 'jb_queue_game_queued_idx')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->dropIndex('jb_queue_game_queued_idx');
            });
        }

        if ($this->indexExists('jb_matchmaking_queue', 'jb_queue_game_player_unique')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->dropUnique('jb_queue_game_player_unique');
            });
        }

        if (! $this->indexExists('jb_matchmaking_queue', 'jb_matchmaking_queue_player_id_unique')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->unique('player_id');
            });
        }

        if ($this->indexExists('jb_matchmaking_queue', 'jb_matchmaking_queue_player_id_idx')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->dropIndex('jb_matchmaking_queue_player_id_idx');
            });
        }

        if (Schema::hasColumn('jb_matchmaking_queue', 'game_type')) {
            Schema::table('jb_matchmaking_queue', function (Blueprint $table): void {
                $table->dropColumn('game_type');
            });
        }

        if ($this->indexExists('jb_championships', 'jb_championships_game_status_idx')) {
            Schema::table('jb_championships', function (Blueprint $table): void {
                $table->dropIndex('jb_championships_game_status_idx');
            });
        }

        if (Schema::hasColumn('jb_championships', 'game_type')) {
            Schema::table('jb_championships', function (Blueprint $table): void {
                $table->dropColumn('game_type');
            });
        }

        if ($this->indexExists('jb_matches', 'jb_matches_game_status_idx')) {
            Schema::table('jb_matches', function (Blueprint $table): void {
                $table->dropIndex('jb_matches_game_status_idx');
            });
        }

        $columns = array_values(array_filter(
            ['game_type', 'ludo_player_ids'],
            fn (string $column): bool => Schema::hasColumn('jb_matches', $column),
        ));

        if ($columns !== []) {
            Schema::table('jb_matches', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.statistics')
                ->where('table_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $index)
                ->exists();
        }

        return collect(DB::select("PRAGMA index_list('{$table}')"))
            ->contains(fn (object $row): bool => ($row->name ?? null) === $index);
    }
};
