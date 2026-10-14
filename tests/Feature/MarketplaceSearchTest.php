<?php

use App\Models\Domain;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

function names(Assert $page): array
{
    return collect($page->toArray()['props']['domains']['data'])->pluck('name')->all();
}

function listing(string $query = ''): TestResponse
{
    return test()->get('/'.($query ? "?$query" : ''));
}

it('renders the marketplace with live domains only', function () {
    Domain::factory()->live()->create(['name' => 'visible.com', 'tld' => 'com']);
    Domain::factory()->pending()->create(['name' => 'pending.com', 'tld' => 'com']);
    Domain::factory()->sold()->create(['name' => 'sold.com', 'tld' => 'com']);

    listing()->assertInertia(fn (Assert $page) => $page
        ->component('Marketplace/Index')
        ->where('domains.data.0.name', 'visible.com')
        ->has('domains.data', 1));
});

it('filters by tld', function () {
    Domain::factory()->create(['name' => 'a.io', 'tld' => 'io']);
    Domain::factory()->create(['name' => 'b.com', 'tld' => 'com']);

    listing('tld=.io')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['a.io']));
});

it('filters by niche', function () {
    Domain::factory()->create(['niche' => 'Fitness', 'name' => 'f.com']);
    Domain::factory()->create(['niche' => 'Pets', 'name' => 'p.com']);

    listing('niche=Fitness')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['f.com']));
});

it('filters by minimum age', function () {
    Domain::factory()->create(['name' => 'old.com', 'registered_year' => now()->year - 15]);
    Domain::factory()->create(['name' => 'new.com', 'registered_year' => now()->year - 2]);

    listing('min_age=10')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['old.com']));
});

it('filters by maximum price in dollars', function () {
    Domain::factory()->create(['name' => 'cheap.com', 'price_cents' => 49_900]);
    Domain::factory()->create(['name' => 'pricey.com', 'price_cents' => 150_000]);

    listing('max_price=500')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['cheap.com']));
});

it('filters by minimum authority', function () {
    Domain::factory()->create(['name' => 'strong.com', 'authority_score' => 70]);
    Domain::factory()->create(['name' => 'weak.com', 'authority_score' => 20]);

    listing('min_authority=50')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['strong.com']));
});

it('combines filters', function () {
    Domain::factory()->create(['name' => 'match.com', 'tld' => 'com', 'niche' => 'Fitness', 'authority_score' => 60, 'registered_year' => now()->year - 12, 'price_cents' => 30_000]);
    Domain::factory()->create(['name' => 'wrongtld.io', 'tld' => 'io', 'niche' => 'Fitness', 'authority_score' => 60, 'registered_year' => now()->year - 12, 'price_cents' => 30_000]);
    Domain::factory()->create(['name' => 'toopricey.com', 'tld' => 'com', 'niche' => 'Fitness', 'authority_score' => 60, 'registered_year' => now()->year - 12, 'price_cents' => 90_000]);

    listing('tld=com&niche=Fitness&min_age=10&max_price=500&min_authority=50')
        ->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['match.com']));
});

it('sorts by price and authority', function () {
    Domain::factory()->create(['name' => 'mid.com', 'price_cents' => 20_000, 'authority_score' => 50]);
    Domain::factory()->create(['name' => 'low.com', 'price_cents' => 10_000, 'authority_score' => 80]);
    Domain::factory()->create(['name' => 'high.com', 'price_cents' => 30_000, 'authority_score' => 20]);

    listing('sort=price_asc')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['low.com', 'mid.com', 'high.com']));
    listing('sort=price_desc')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['high.com', 'mid.com', 'low.com']));
    listing('sort=authority_desc')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['low.com', 'mid.com', 'high.com']));
});

it('does not accept unknown sort values', function () {
    listing('sort=DROP')->assertSessionHasErrors('sort');
});

it('searches free text through Scout', function () {
    Domain::factory()->create(['name' => 'yogabliss.com', 'niche' => 'Fitness']);
    Domain::factory()->create(['name' => 'catfood.com', 'niche' => 'Pets']);

    listing('q=yoga')->assertInertia(fn (Assert $p) => expect(names($p))->toBe(['yogabliss.com']));
});

it('paginates 12 per page and keeps filters in links', function () {
    Domain::factory()->count(15)->create(['tld' => 'com']);

    listing('tld=com')->assertInertia(fn (Assert $p) => $p
        ->has('domains.data', 12)
        ->where('domains.meta.total', 15)
        ->where('domains.links.next', fn ($url) => str_contains($url, 'tld=com')));
});

it('does not run N+1 queries on the listing', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        listing()->assertOk();

        return count(DB::getQueryLog());
    };

    Domain::factory()->count(12)->create();
    listing(); // warm the facet cache so both measurements are comparable
    $few = $countQueries();

    Domain::factory()->count(30)->create();
    $many = $countQueries();

    expect($many)->toBe($few);
});

it('shows live and sold detail pages but hides drafts and pending from the public', function () {
    $live = Domain::factory()->live()->create();
    $sold = Domain::factory()->sold()->create();
    $pending = Domain::factory()->pending()->create();

    $this->get("/domains/{$live->id}")->assertOk()->assertInertia(fn (Assert $p) => $p->component('Marketplace/Show')->where('domain.name', $live->name));
    $this->get("/domains/{$sold->id}")->assertOk();
    $this->get("/domains/{$pending->id}")->assertForbidden();
});
