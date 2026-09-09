<?php

namespace App\Jobs;

use App\Models\Endpoint;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Throwable;

class RunEndpointCheck implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Give the HTTP call (10s timeout + 5s connect timeout) room to finish
     * before the job itself is considered stuck.
     */
    public int $timeout = 20;

    /**
     * Keep a duplicate check for the same endpoint from queueing up behind
     * one that's still running.
     */
    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $endpointId
    ) {
        //
    }

    public function uniqueId(): string
    {
        return (string) $this->endpointId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $endpoint = Endpoint::query()->with('site')->find($this->endpointId);

        if ($endpoint === null) {
            return;
        }

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withOptions(['http_errors' => false])
                ->get($endpoint->fullUrl());

            $endpoint->registerCheck($response->status(), $response->body());
        } catch (Throwable $e) {
            // Every attempt should produce exactly one endpoint_checks row,
            // whatever went wrong (DNS failure, timeout, TLS error, ...) —
            // that table is the source of truth for uptime, not the
            // queue's own failed_jobs bookkeeping, so this is deliberately
            // not rethrown.
            $endpoint->registerError($e->getMessage());
        }
    }
}
