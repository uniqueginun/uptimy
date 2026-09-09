<?php

namespace App\Console\Commands;

use App\Jobs\RunEndpointCheck;
use App\Models\Endpoint;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('endpoint-checks:dispatch-due')]
#[Description('Dispatch a check job for every endpoint that is due')]
class DispatchDueEndpointChecks extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Endpoint::due()->pluck('id')->each(
            fn (int $endpointId) => RunEndpointCheck::dispatch($endpointId)
        );

        return self::SUCCESS;
    }
}
