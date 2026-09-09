<?php

use App\Models\EndpointCheck;

test('it deletes checks older than the retention window and keeps recent ones', function () {
    config(['uptime.check_retention_days' => 90]);

    $old = EndpointCheck::factory()->create(['checked_at' => now()->subDays(91)]);
    $recent = EndpointCheck::factory()->create(['checked_at' => now()->subDays(10)]);

    $this->artisan('endpoint-checks:prune')->assertSuccessful();

    expect(EndpointCheck::find($old->id))->toBeNull();
    expect(EndpointCheck::find($recent->id))->not->toBeNull();
});
