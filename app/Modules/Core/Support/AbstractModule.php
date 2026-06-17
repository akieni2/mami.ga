<?php

namespace App\Modules\Core\Support;

use App\Modules\Core\Contracts\MamiModuleInterface;

abstract class AbstractModule implements MamiModuleInterface
{
    public function enabled(): bool
    {
        return (bool) config('mami.modules.'.$this->slug(), false);
    }

    public function register(): void {}

    public function boot(): void {}
}
