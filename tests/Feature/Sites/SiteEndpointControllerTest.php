<?php

use App\Models\Endpoint;
use App\Models\Site;
use App\Models\User;

test('guests cannot create an endpoint', function () {
    $site = Site::factory()->create();

    $response = $this->post(route('endpoints.store', $site->domain), [
        'uri' => '/health',
        'interval' => 15,
    ]);

    $response->assertRedirect(route('login'));
});

test('a user cannot add an endpoint to another user\'s site', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->for($owner)->create();
    $attacker = User::factory()->create();

    $response = $this->actingAs($attacker)->post(route('endpoints.store', $site->domain), [
        'uri' => '/health',
        'interval' => 15,
    ]);

    $response->assertNotFound();
    expect($site->endpoints()->count())->toBe(0);
});

test('a user cannot delete an endpoint from another user\'s site', function () {
    $owner = User::factory()->create();
    $site = Site::factory()->for($owner)->create();
    $endpoint = Endpoint::factory()->for($site)->create();
    $attacker = User::factory()->create();

    $response = $this->actingAs($attacker)->delete(route('endpoints.destroy', [$site->domain, $endpoint->id]));

    $response->assertNotFound();
    expect(Endpoint::find($endpoint->id))->not->toBeNull();
});

test('uri and interval are required together', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('endpoints.store', $site->domain), []);

    $response->assertSessionHasErrors(['uri', 'interval']);
});

test('the uri must start with a slash', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('endpoints.store', $site->domain), [
        'uri' => 'health',
        'interval' => 15,
    ]);

    $response->assertSessionHasErrors('uri');
});

test('the interval must be one of the offered options', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('endpoints.store', $site->domain), [
        'uri' => '/health',
        'interval' => 7,
    ]);

    $response->assertSessionHasErrors('interval');
});

test('the owner can add an endpoint to their site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();

    $response = $this->actingAs($user)->post(route('endpoints.store', $site->domain), [
        'uri' => '/health',
        'interval' => 15,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sites.show', $site->domain));

    expect($site->endpoints()->where('uri', '/health')->where('interval', 15)->exists())->toBeTrue();
});

test('the owner can delete an endpoint from their site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->for($user)->create();
    $endpoint = Endpoint::factory()->for($site)->create();

    $response = $this->actingAs($user)->delete(route('endpoints.destroy', [$site->domain, $endpoint->id]));

    $response->assertRedirect(route('sites.show', $site->domain));
    expect(Endpoint::find($endpoint->id))->toBeNull();
});
