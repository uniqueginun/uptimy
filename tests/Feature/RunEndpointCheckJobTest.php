<?php

use App\Enums\CheckStatus;
use App\Jobs\RunEndpointCheck;
use App\Models\Endpoint;
use App\Models\Site;
use Illuminate\Support\Facades\Http;

test('a successful response is recorded as an up check', function () {
    Http::preventStrayRequests();
    Http::fake(['stacklab.test/*' => Http::response('ok', 200)]);

    $site = Site::factory()->create(['url' => 'https://stacklab.test']);
    $endpoint = Endpoint::factory()->for($site)->create(['uri' => '/health']);

    (new RunEndpointCheck($endpoint->id))->handle();

    $check = $endpoint->checks()->latest('checked_at')->first();

    expect($check->status)->toBe(CheckStatus::Up);
    expect($check->http_status_code)->toBe(200);
    expect($endpoint->fresh()->last_run_at)->not->toBeNull();
});

test('a non-2xx response is recorded as a down check without throwing', function () {
    Http::preventStrayRequests();
    Http::fake(['stacklab.test/*' => Http::response('not found', 404)]);

    $site = Site::factory()->create(['url' => 'https://stacklab.test']);
    $endpoint = Endpoint::factory()->for($site)->create(['uri' => '/missing']);

    (new RunEndpointCheck($endpoint->id))->handle();

    $check = $endpoint->checks()->latest('checked_at')->first();

    expect($check->status)->toBe(CheckStatus::Down);
    expect($check->http_status_code)->toBe(404);
});

test('a connection failure is recorded as an error check without throwing', function () {
    Http::preventStrayRequests();
    Http::fake(['stacklab.test/*' => Http::failedConnection()]);

    $site = Site::factory()->create(['url' => 'https://stacklab.test']);
    $endpoint = Endpoint::factory()->for($site)->create(['uri' => '/down']);

    (new RunEndpointCheck($endpoint->id))->handle();

    $check = $endpoint->checks()->latest('checked_at')->first();

    expect($check->status)->toBe(CheckStatus::Error);
    expect($check->http_status_code)->toBeNull();
    expect($check->error_message)->not->toBeNull();
});

test('the job does nothing when the endpoint id does not exist', function () {
    Http::preventStrayRequests();

    expect(fn () => (new RunEndpointCheck(999999))->handle())->not->toThrow(Throwable::class);
});
