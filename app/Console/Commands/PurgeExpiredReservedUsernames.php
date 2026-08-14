<?php

namespace App\Console\Commands;

use App\Models\ReservedUsername;
use Illuminate\Console\Command;

class PurgeExpiredReservedUsernames extends Command
{
    protected $signature = 'shipped:purge-reserved-usernames';

    protected $description = 'Release expired username reservations so they become available again';

    public function handle(): int
    {
        $deleted = ReservedUsername::expired()->delete();

        $this->info("Released {$deleted} expired username reservation(s).");

        return self::SUCCESS;
    }
}
