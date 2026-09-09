<?php

namespace App\Console\Commands;

use App\Models\EndpointCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('endpoint-checks:prune')]
#[Description('Delete endpoint check history older than the configured retention window')]
class PruneEndpointChecks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(config('uptime.check_retention_days'));

        $deleted = EndpointCheck::where('checked_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} endpoint check(s) older than {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
