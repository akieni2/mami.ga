<?php

use App\Modules\JbLudo\Http\Controllers\LeaderboardController;
use App\Modules\JbLudo\Http\Controllers\MatchController;
use App\Modules\JbLudo\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:jb_ludo'])->group(function (): void {
    Route::get('/profile/me', [ProfileController::class, 'me']);
    Route::post('/profile', [ProfileController::class, 'store']);
    Route::post('/profile/heartbeat', [ProfileController::class, 'heartbeat']);
    Route::get('/players/search', [ProfileController::class, 'search']);

    Route::post('/matches/invite', [MatchController::class, 'invite']);
    Route::post('/matches/invites/{invite}/accept', [MatchController::class, 'acceptInvite']);
    Route::post('/matches/invites/{invite}/decline', [MatchController::class, 'declineInvite']);
    Route::post('/matches/quick', [MatchController::class, 'quick']);
    Route::post('/matches/solo', [MatchController::class, 'solo']);
    Route::post('/matches/queue/leave', [MatchController::class, 'leaveQueue']);
    Route::get('/matches/history', [MatchController::class, 'history']);
    Route::get('/matches/{match}', [MatchController::class, 'show']);
    Route::post('/matches/{match}/moves', [MatchController::class, 'move']);
    Route::post('/matches/{match}/resign', [MatchController::class, 'resign']);
    Route::post('/matches/{match}/disconnect', [MatchController::class, 'disconnect']);
    Route::post('/matches/{match}/reconnect', [MatchController::class, 'reconnect']);

    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
});
