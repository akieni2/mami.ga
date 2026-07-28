<?php

namespace Tests\Feature\JbLudo;

use App\Models\User;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JbLudoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mami.modules.jb_ludo' => true]);
    }

    public function test_player_can_create_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/jb-ludo/profile', [
            'pseudo' => 'Akieni',
            'phone' => '+24106001122',
            'city' => 'Owendo',
            'level' => 'intermediate',
        ])->assertCreated()
            ->assertJsonPath('data.pseudo', 'Akieni')
            ->assertJsonPath('data.points', 0);

        $this->assertDatabaseHas('jb_player_profiles', [
            'user_id' => $user->id,
            'phone' => '+24106001122',
        ]);
    }

    public function test_duplicate_phone_rejected(): void
    {
        $existing = User::factory()->create();
        PlayerProfile::query()->create([
            'user_id' => $existing->id,
            'pseudo' => 'Existant',
            'phone' => '+24106009999',
            'country' => 'Gabon',
            'level' => 'beginner',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/jb-ludo/profile', [
            'pseudo' => 'Nouveau',
            'phone' => '+24106009999',
        ])->assertStatus(422);
    }

    public function test_quick_matchmaking_pairs_two_players(): void
    {
        [$a, $pa] = $this->playerWithProfile('Alpha', '+24106000001');
        [$b, $pb] = $this->playerWithProfile('Beta', '+24106000002');

        Sanctum::actingAs($a);
        $this->postJson('/api/jb-ludo/matches/quick')
            ->assertOk()
            ->assertJsonPath('data.queued', true);

        Sanctum::actingAs($b);
        $this->postJson('/api/jb-ludo/matches/quick')
            ->assertOk()
            ->assertJsonPath('data.queued', false)
            ->assertJsonPath('data.match.status', 'in_progress');

        $this->assertDatabaseCount('jb_matches', 1);
    }

    public function test_leaderboard_requires_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/jb-ludo/leaderboard')->assertStatus(422);
    }

    /**
     * @return array{0: User, 1: PlayerProfile}
     */
    private function playerWithProfile(string $pseudo, string $phone): array
    {
        $user = User::factory()->create();
        $profile = PlayerProfile::query()->create([
            'user_id' => $user->id,
            'pseudo' => $pseudo,
            'phone' => $phone,
            'city' => 'Libreville',
            'country' => 'Gabon',
            'level' => 'intermediate',
            'points' => 0,
        ]);

        return [$user, $profile];
    }
}
