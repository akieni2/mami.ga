<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jb_championships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('max_participants')->default(1000);
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedInteger('rounds_count')->default(0);
            $table->unsignedInteger('current_round')->default(1);
            $table->timestamp('registration_closes_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('bracket_generated_at')->nullable();
            $table->foreignId('champion_player_id')->nullable()->constrained('jb_player_profiles')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
        });

        Schema::create('jb_championship_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('championship_id')->constrained('jb_championships')->cascadeOnDelete();
            $table->foreignId('player_profile_id')->constrained('jb_player_profiles')->cascadeOnDelete();
            $table->unsignedInteger('seed_number')->nullable();
            $table->string('status', 30)->default('registered');
            $table->unsignedInteger('bye_count')->default(0);
            $table->timestamp('eliminated_at')->nullable();
            $table->timestamps();

            $table->unique(['championship_id', 'player_profile_id'], 'jb_champ_participant_unique');
            $table->index(['championship_id', 'seed_number']);
            $table->index(['championship_id', 'status']);
        });

        Schema::table('jb_matches', function (Blueprint $table): void {
            $table->foreignId('championship_id')->nullable()->after('mode')->constrained('jb_championships')->nullOnDelete();
            $table->unsignedInteger('championship_round')->nullable()->after('championship_id');
            $table->unsignedInteger('championship_match_number')->nullable()->after('championship_round');

            $table->index(['championship_id', 'championship_round'], 'jb_matches_champ_round_idx');
        });
    }

    public function down(): void
    {
        Schema::table('jb_matches', function (Blueprint $table): void {
            $table->dropIndex('jb_matches_champ_round_idx');
            $table->dropConstrainedForeignId('championship_id');
            $table->dropColumn(['championship_round', 'championship_match_number']);
        });

        Schema::dropIfExists('jb_championship_participants');
        Schema::dropIfExists('jb_championships');
    }
};
