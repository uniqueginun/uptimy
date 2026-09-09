<?php

use App\Models\Endpoint;

test('an endpoint that has never run is due', function () {
    $endpoint = Endpoint::factory()->create([
        'interval' => 15,
        'last_run_at' => null,
    ]);

    expect(Endpoint::due()->pluck('id'))->toContain($endpoint->id);
});

test('an endpoint is due once its interval has elapsed since its last run', function () {
    $endpoint = Endpoint::factory()->create([
        'interval' => 15,
        'last_run_at' => now()->subMinutes(16),
    ]);

    expect(Endpoint::due()->pluck('id'))->toContain($endpoint->id);
});

test('an endpoint is not due before its interval has elapsed', function () {
    $endpoint = Endpoint::factory()->create([
        'interval' => 15,
        'last_run_at' => now()->subMinutes(5),
    ]);

    expect(Endpoint::due()->pluck('id'))->not->toContain($endpoint->id);
});

test('due only matches endpoints whose own interval has elapsed', function () {
    $due = Endpoint::factory()->create([
        'interval' => 1,
        'last_run_at' => now()->subMinutes(2),
    ]);

    $notDue = Endpoint::factory()->create([
        'interval' => 60,
        'last_run_at' => now()->subMinutes(2),
    ]);

    $dueIds = Endpoint::due()->pluck('id');

    expect($dueIds)->toContain($due->id);
    expect($dueIds)->not->toContain($notDue->id);
});
