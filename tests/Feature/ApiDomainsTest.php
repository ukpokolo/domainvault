<?php

use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => RateLimiter::clear('api'));

it('requires authentication', function () {
    $this->getJson('/api/domains')->assertUnauthorized();
    $this->get('/api/domains')->assertUnauthorized(); // no Accept header: still JSON, not a redirect
});

it('issues a token for valid credentials and rejects bad ones', function () {
    $user = User::factory()->create(['password' => 'secret-pass']);

    $token = $this->postJson('/api/tokens', ['email' => $user->email, 'password' => 'secret-pass', 'device_name' => 'cli'])
        ->assertCreated()->json('token');

    $this->withToken($token)->getJson('/api/domains')->assertOk();

    $this->postJson('/api/tokens', ['email' => $user->email, 'password' => 'wrong', 'device_name' => 'cli'])->assertUnprocessable();
    $this->postJson('/api/tokens', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

it('rate limits token creation attempts', function () {
    foreach (range(1, 5) as $_) {
        $this->postJson('/api/tokens', ['email' => 'a@b.com', 'password' => 'x', 'device_name' => 'cli'])->assertUnprocessable();
    }

    $this->postJson('/api/tokens', ['email' => 'a@b.com', 'password' => 'x', 'device_name' => 'cli'])->assertStatus(429);
});

it('rejects tokens that lack the domains:read ability', function () {
    Sanctum::actingAs(User::factory()->create(), ['something:else']);

    $this->getJson('/api/domains')->assertForbidden();
});

it('lists live domains through the resource with pagination metadata', function () {
    Sanctum::actingAs(User::factory()->create(), ['domains:read']);
    Domain::factory()->count(3)->create();
    Domain::factory()->pending()->create();

    $this->getJson('/api/domains')->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'tld', 'registered_year', 'age', 'backlinks', 'referring_domains', 'authority_score', 'niche', 'price_cents', 'price', 'status', 'notes']],
            'links', 'meta' => ['current_page', 'per_page', 'total'],
        ]);
});

it('filters, sorts and paginates', function () {
    Sanctum::actingAs(User::factory()->create(), ['domains:read']);
    Domain::factory()->create(['name' => 'old.com', 'tld' => 'com', 'registered_year' => now()->year - 15, 'price_cents' => 20_000]);
    Domain::factory()->create(['name' => 'older.com', 'tld' => 'com', 'registered_year' => now()->year - 20, 'price_cents' => 10_000]);
    Domain::factory()->create(['name' => 'new.io', 'tld' => 'io', 'registered_year' => now()->year - 1]);

    $this->getJson('/api/domains?tld=com&min_age=10&sort=price_asc')
        ->assertOk()->assertJsonPath('data.0.name', 'older.com')->assertJsonPath('data.1.name', 'old.com')->assertJsonCount(2, 'data');

    $this->getJson('/api/domains?per_page=1')->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 3);
});

it('validates query parameters', function () {
    Sanctum::actingAs(User::factory()->create(), ['domains:read']);

    $this->getJson('/api/domains?min_authority=500&per_page=999&sort=bogus')
        ->assertUnprocessable()->assertJsonValidationErrors(['min_authority', 'per_page', 'sort']);
});

it('shows a live domain and hides unpublished ones', function () {
    Sanctum::actingAs(User::factory()->create(), ['domains:read']);
    $live = Domain::factory()->create();
    $pending = Domain::factory()->pending()->create();

    $this->getJson("/api/domains/{$live->id}")->assertOk()->assertJsonPath('data.name', $live->name);
    $this->getJson("/api/domains/{$pending->id}")->assertNotFound();
});

it('throttles authenticated clients after 60 requests a minute', function () {
    Sanctum::actingAs(User::factory()->create(), ['domains:read']);

    foreach (range(1, 60) as $_) {
        $this->getJson('/api/domains')->assertOk();
    }

    $this->getJson('/api/domains')->assertStatus(429)->assertHeader('Retry-After');
});

it('revokes the current token on delete', function () {
    $user = User::factory()->create(['password' => 'secret-pass']);
    $token = $this->postJson('/api/tokens', ['email' => $user->email, 'password' => 'secret-pass', 'device_name' => 'cli'])->json('token');

    $this->withToken($token)->deleteJson('/api/tokens')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
