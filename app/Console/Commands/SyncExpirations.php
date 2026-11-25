<?php

namespace App\Console\Commands;

use App\Services\MembershipService;
use Illuminate\Console\Command;

class SyncExpirations extends Command
{
    protected $signature = 'membership:sync-expirations';

    protected $description = 'Mark lapsed memberships and approved transactions as expired';

    public function handle(MembershipService $service): int
    {
        $service->syncExpirations();
        $this->info('Expired memberships and transactions have been updated.');

        return self::SUCCESS;
    }
}
