<?php

namespace App\Modules\Core\Contracts;

interface MamiModuleInterface
{
    public function name(): string;

    public function slug(): string;

    public function enabled(): bool;

    public function register(): void;

    public function boot(): void;
}
