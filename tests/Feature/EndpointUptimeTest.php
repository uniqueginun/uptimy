<?php

use App\Enums\CheckStatus;
use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;

test('a freshly created endpoint has no uptime percentage yet', function () {
    $endpoint = Endpoint::factory()->create();

    expect($endpoint->uptime_percentage)->toBeNull();
    expect($endpoint->uptime_percentage_24h)->toBeNull();
});

test('serializing an endpoint with no checks does not error', function () {
    $endpoint = Endpoint::factory()->create();

    $data = (new EndpointResource($endpoint))->resolve();

    expect($data['uptime'])->toBeNull();
    expect($data['uptime_24h'])->toBeNull();
});

test('uptime percentage reflects the ratio of successful checks', function () {
    $endpoint = Endpoint::factory()->create();

    $endpoint->checks()->createMany([
        ['checked_at' => now(), 'http_status_code' => 200, 'status' => CheckStatus::Up],
        ['checked_at' => now(), 'http_status_code' => 200, 'status' => CheckStatus::Up],
        ['checked_at' => now(), 'http_status_code' => 200, 'status' => CheckStatus::Up],
        ['checked_at' => now(), 'http_status_code' => 500, 'status' => CheckStatus::Down],
    ]);

    expect($endpoint->fresh()->uptime_percentage)->toBe(75.0);
});

test('the 24 hour uptime window excludes older checks', function () {
    $endpoint = Endpoint::factory()->create();

    $endpoint->checks()->create([
        'checked_at' => now()->subDays(2),
        'http_status_code' => 500,
        'status' => CheckStatus::Down,
    ]);

    $endpoint->checks()->create([
        'checked_at' => now(),
        'http_status_code' => 200,
        'status' => CheckStatus::Up,
    ]);

    $fresh = $endpoint->fresh();

    expect($fresh->uptime_percentage)->toBe(50.0);
    expect($fresh->uptime_percentage_24h)->toBe(100.0);
});
