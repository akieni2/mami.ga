<?php

namespace App\Modules\JbLudo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\JbLudo\Http\Resources\GameMatchResource;
use App\Modules\JbLudo\Enums\GameType;
use App\Modules\JbLudo\Models\FriendInvite;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\PlayerProfile;
use App\Modules\JbLudo\Services\MatchLifecycleService;
use App\Modules\JbLudo\Services\MatchmakingService;
use App\Modules\JbLudo\Services\PlayerProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends Controller
{
    public function __construct(
        private readonly PlayerProfileService $profiles,
        private readonly MatchmakingService $matchmaking,
        private readonly MatchLifecycleService $lifecycle,
    ) {}

    public function invite(Request $request): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $data = $request->validate([
            'to_player_id' => ['required', 'integer', 'exists:jb_player_profiles,id'],
        ]);

        $to = PlayerProfile::query()->findOrFail($data['to_player_id']);
        $invite = $this->matchmaking->invite($player, $to);

        return ApiResponse::success([
            'id' => $invite->id,
            'status' => $invite->status->value,
            'from_player_id' => $invite->from_player_id,
            'to_player_id' => $invite->to_player_id,
        ], 'Invitation envoyée', 201);
    }

    public function acceptInvite(Request $request, FriendInvite $invite): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $match = $this->matchmaking->acceptInvite($invite, $player);

        return ApiResponse::success(new GameMatchResource($match), 'Partie démarrée');
    }

    public function declineInvite(Request $request, FriendInvite $invite): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $invite = $this->matchmaking->declineInvite($invite, $player);

        return ApiResponse::success(['id' => $invite->id, 'status' => $invite->status->value]);
    }

    public function quick(Request $request): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $data = $request->validate([
            'game_type' => ['nullable', 'in:damier,ludo'],
        ]);
        $result = $this->matchmaking->enqueueQuick($player, GameType::from($data['game_type'] ?? 'damier'));

        return ApiResponse::success([
            'queued' => $result['queued'],
            'match' => $result['match'] ? new GameMatchResource($result['match']) : null,
        ], $result['queued'] ? 'En file d\'attente' : 'Adversaire trouvé');
    }

    public function solo(Request $request): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $data = $request->validate([
            'game_type' => ['required', 'in:damier,ludo'],
            'difficulty' => ['nullable', 'in:beginner,medium,hard'],
        ]);

        $match = $this->lifecycle->createSoloMatch(
            $player,
            GameType::from($data['game_type']),
            $data['difficulty'] ?? 'medium',
        );

        return ApiResponse::success(new GameMatchResource($match), 'Partie entrainement creee', 201);
    }

    public function leaveQueue(Request $request): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $this->matchmaking->leaveQueue($player);

        return ApiResponse::success(null, 'File quittée');
    }

    public function show(Request $request, GameMatch $match): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $isParticipant = $match->game_type === GameType::Ludo
            ? $match->ludoColor($player) !== null
            : $match->playerColor($player) !== null;

        if (! $isParticipant && ! $request->user()->isAdmin()) {
            abort(403);
        }

        $this->lifecycle->resolveExpiredGrace($match);

        return ApiResponse::success(new GameMatchResource(
            $match->fresh(['whitePlayer', 'blackPlayer', 'winner', 'moves'])
        ));
    }

    public function move(Request $request, GameMatch $match): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $data = $request->validate([
            'path' => ['required', 'array', 'min:1'],
            'path.*.r' => ['nullable', 'integer', 'min:0', 'max:9'],
            'path.*.c' => ['nullable', 'integer', 'min:0', 'max:9'],
            'path.*.action' => ['nullable', 'string', 'in:roll,move'],
            'path.*.piece' => ['nullable', 'integer', 'min:0', 'max:3'],
        ]);

        $updated = $this->lifecycle->playMove($match, $player, $data['path']);

        return ApiResponse::success(new GameMatchResource($updated), 'Coup enregistré');
    }

    public function resign(Request $request, GameMatch $match): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $updated = $this->lifecycle->resign($match, $player);

        return ApiResponse::success(new GameMatchResource($updated), 'Abandon enregistré');
    }

    public function disconnect(Request $request, GameMatch $match): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $updated = $this->lifecycle->markDisconnected($match, $player);

        return ApiResponse::success(new GameMatchResource($updated), 'Déconnexion signalée');
    }

    public function reconnect(Request $request, GameMatch $match): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());
        $updated = $this->lifecycle->reconnect($match, $player);

        return ApiResponse::success(new GameMatchResource($updated), 'Reconnexion OK');
    }

    public function history(Request $request): JsonResponse
    {
        $player = $this->profiles->forUser($request->user());

        $items = GameMatch::query()
            ->with(['whitePlayer', 'blackPlayer', 'winner'])
            ->where(function ($q) use ($player): void {
                $q->where('white_player_id', $player->id)
                    ->orWhere('black_player_id', $player->id)
                    ->orWhereJsonContains('ludo_player_ids->red', $player->id)
                    ->orWhereJsonContains('ludo_player_ids->blue', $player->id)
                    ->orWhereJsonContains('ludo_player_ids->green', $player->id)
                    ->orWhereJsonContains('ludo_player_ids->yellow', $player->id);
            })
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return ApiResponse::success(GameMatchResource::collection($items));
    }
}
