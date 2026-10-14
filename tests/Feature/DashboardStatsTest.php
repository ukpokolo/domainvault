<?php

use App\Models\Domain;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\DashboardStats;

it('reports listings by status including zero counts', function () {
    Domain::factory()->live()->count(3)->create();
    Domain::factory()->pending()->create();

    expect(app(DashboardStats::class)->all()['by_status'])
        ->toBe(['draft' => 0, 'pending' => 1, 'live' => 3, 'sold' => 0]);
});

it('sums revenue from paid orders only and ranks niches by revenue', function () {
    $fitness = Domain::factory()->sold()->create(['niche' => 'Fitness', 'price_cents' => 50_000]);
    $pets = Domain::factory()->sold()->create(['niche' => 'Pets', 'price_cents' => 20_000]);
    $unpaid = Domain::factory()->create(['niche' => 'Legal', 'price_cents' => 99_000]);

    $paid = Order::factory()->paid()->for(User::factory())->create(['total_cents' => 70_000]);
    $paid->items()->createMany([
        ['domain_id' => $fitness->id, 'price_cents' => 50_000],
        ['domain_id' => $pets->id, 'price_cents' => 20_000],
    ]);
    $pending = Order::factory()->for(User::factory())->create(['total_cents' => 99_000]);
    $pending->items()->create(['domain_id' => $unpaid->id, 'price_cents' => 99_000]);

    $stats = app(DashboardStats::class)->all();

    expect($stats['revenue_cents'])->toBe(70_000)
        ->and($stats['orders_paid'])->toBe(1)
        ->and($stats['top_niches'])->toBe([
            ['niche' => 'Fitness', 'sales' => 1, 'revenue_cents' => 50_000],
            ['niche' => 'Pets', 'sales' => 1, 'revenue_cents' => 20_000],
        ]);
});
