<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;

it('fails only unpaid orders older than the threshold', function () {
    $stale = Order::factory()->create(['created_at' => now()->subHours(30)]);
    $fresh = Order::factory()->create(['created_at' => now()->subHours(2)]);
    $paid = Order::factory()->paid()->create(['created_at' => now()->subDays(5)]);

    $this->artisan('orders:expire-stale')->expectsOutput('Expired 1 stale order(s).')->assertSuccessful();

    expect($stale->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($fresh->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($paid->fresh()->status)->toBe(OrderStatus::Paid);
});

it('is registered on the scheduler hourly', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($e) => str_contains($e->command, 'orders:expire-stale'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 * * * *');
});
