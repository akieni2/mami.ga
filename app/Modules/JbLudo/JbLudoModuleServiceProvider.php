<?php

namespace App\Modules\JbLudo;

use App\Modules\JbLudo\Models\FriendInvite;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class JbLudoModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::prefix('api/jb-ludo')
            ->middleware('api')
            ->group(base_path('app/Modules/JbLudo/Routes/api.php'));

        Route::bind('match', function (string $value): GameMatch {
            return GameMatch::query()->findOrFail($value);
        });

        Route::bind('invite', function (string $value): FriendInvite {
            return FriendInvite::query()->findOrFail($value);
        });

        Route::bind('player', function (string $value): PlayerProfile {
            return PlayerProfile::query()->findOrFail($value);
        });
    }
}
