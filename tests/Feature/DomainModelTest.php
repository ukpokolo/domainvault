<?php

use App\Models\Domain;
use Database\Seeders\DatabaseSeeder;

it('computes age from the registration year', function () {
    $domain = Domain::factory()->make(['registered_year' => now()->year - 12]);

    expect($domain->age)->toBe(12);
});

it('filters by minimum age', function () {
    Domain::factory()->create(['registered_year' => now()->year - 12]);
    Domain::factory()->create(['registered_year' => now()->year - 3]);

    expect(Domain::minAge(10)->count())->toBe(1);
});

it('scopes live domains', function () {
    Domain::factory()->live()->create();
    Domain::factory()->pending()->create();
    Domain::factory()->sold()->create();

    expect(Domain::live()->count())->toBe(1);
});

it('keeps tld consistent with the name in the factory', function () {
    $domain = Domain::factory()->create();

    expect($domain->name)->toEndWith('.'.$domain->tld);
});

it('seeds demo users and about 200 domains across statuses', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Domain::count())->toBe(200)
        ->and(Domain::distinct()->pluck('status')->map->value->sort()->values()->all())
        ->toBe(['draft', 'live', 'pending', 'sold'])
        ->and(Domain::where('authority_score', '>', 100)->count())->toBe(0);

    $this->assertDatabaseHas('users', ['email' => 'admin@domainvault.test', 'role' => 'admin']);
    $this->assertDatabaseHas('users', ['email' => 'buyer@domainvault.test', 'role' => 'buyer']);
});
