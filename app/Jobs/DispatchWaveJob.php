<?php

namespace App\Jobs;

use App\Services\RideDispatchEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchWaveJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $rideId,
        public int $waveIndex,
    ) {}

    public function handle(RideDispatchEngine $engine): void
    {
        $engine->processWave($this->rideId, $this->waveIndex);
    }
}
