<?php

namespace App\Console\Commands;

use App\Services\CheckInService;
use Illuminate\Console\Command;

class ExpireCheckIns extends Command
{
    protected $signature = 'checkins:expire-stale';
    protected $description = 'Mark stale active check-ins as expired';

    public function handle(CheckInService $service): int
    {
        $count = $service->expireStale();
        $this->info("Expired {$count} check-ins.");
        return self::SUCCESS;
    }
}
