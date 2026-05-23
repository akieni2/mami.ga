<?php

namespace App\Console\Commands;

use App\Services\DriverPresenceService;
use Illuminate\Console\Command;

class MarkStaleDriversOfflineCommand extends Command
{
    protected $signature = 'drivers:mark-offline';

    protected $description = 'Mark drivers without recent GPS heartbeat as offline';

    public function handle(DriverPresenceService $presenceService): int
    {
        $count = $presenceService->markStaleDriversOffline();

        $this->info("Marked {$count} driver(s) as offline.");

        return self::SUCCESS;
    }
}
