<?php

use App\Models\Endpoint;
use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $site = Site::factory()->create();

    $response = $this->get(route('sites.show', $site->domain));

    $response->assertRedirect(route('login'));
});

test('a user cannot view another user\'s site', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->for($owner)->create();
    $viewer = User::factory()->create();

    $response = $this->actingAs($viewer)->get(route('sites.show', $site->domain));

    $response->assertNotFound();
});

test('a site page renders without error when an endpoint has no checks yet', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();
    Endpoint::factory()->for($site)->create();

    $response = $this->actingAs($user)->get(route('sites.show', $site->domain));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sites/show')
            ->where('site.endpoints.data.0.uptime', null)
            ->where('site.endpoints.data.0.uptime_24h', null),
        );
});

test('a site page shows the calculated uptime for an endpoint with checks', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();
    $endpoint = Endpoint::factory()->for($site)->create();

    $endpoint->checks()->create(['checked_at' => now(), 'http_status_code' => 200, 'status' => 'up']);
    $endpoint->checks()->create(['checked_at' => now(), 'http_status_code' => 500, 'status' => 'down']);

    $response = $this->actingAs($user)->get(route('sites.show', $site->domain));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('sites/show')
            ->where('site.endpoints.data.0.uptime', 50),
        );
});
