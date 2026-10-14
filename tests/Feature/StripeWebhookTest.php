<?php

use App\Enums\DomainStatus;
use App\Enums\OrderStatus;
use App\Models\Domain;
use App\Models\Order;
use App\Models\User;

const WEBHOOK_SECRET = 'whsec_test_secret';

beforeEach(function () {
    config(['services.stripe.webhook_secret' => WEBHOOK_SECRET]);
});

function stripeEvent(string $type, array $object): string
{
    return json_encode([
        'id' => 'evt_'.uniqid(),
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object + ['object' => 'checkout.session']],
    ]);
}

function postWebhook(string $payload, ?string $secret = WEBHOOK_SECRET, ?int $timestamp = null)
{
    $headers = ['CONTENT_TYPE' => 'application/json'];
    if ($secret !== null) {
        $t = $timestamp ?? time();
        $headers['HTTP_STRIPE_SIGNATURE'] = "t=$t,v1=".hash_hmac('sha256', "$t.$payload", $secret);
    }

    return test()->call('POST', '/stripe/webhook', [], [], [], $headers, $payload);
}

function pendingOrderFor(Domain ...$domains): Order
{
    $order = Order::factory()->for(User::factory())->create([
        'stripe_session_id' => 'cs_test_abc',
        'total_cents' => collect($domains)->sum('price_cents'),
    ]);
    foreach ($domains as $d) {
        $order->items()->create(['domain_id' => $d->id, 'price_cents' => $d->price_cents]);
        $order->user->cartItems()->create(['domain_id' => $d->id]);
    }

    return $order;
}

$completed = fn (string $status = 'paid') => stripeEvent('checkout.session.completed', [
    'id' => 'cs_test_abc', 'payment_status' => $status, 'payment_intent' => 'pi_123',
]);

it('marks the order paid and the domains sold', function () use ($completed) {
    $domains = Domain::factory()->count(2)->create();
    $order = pendingOrderFor(...$domains);
    $other = User::factory()->create();
    $other->cartItems()->create(['domain_id' => $domains[0]->id]);

    postWebhook($completed())->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($order->fresh()->stripe_payment_intent)->toBe('pi_123')
        ->and($order->fresh()->paid_at)->not->toBeNull();
    $domains->each(fn ($d) => expect($d->fresh()->status)->toBe(DomainStatus::Sold)->and($d->fresh()->sold_at)->not->toBeNull());
    // Sold domains vanish from every cart.
    $this->assertDatabaseCount('cart_items', 0);
});

it('is idempotent when Stripe redelivers the event', function () use ($completed) {
    $domain = Domain::factory()->create();
    $order = pendingOrderFor($domain);

    postWebhook($completed())->assertOk();
    $soldAt = $domain->fresh()->sold_at;
    $paidAt = $order->fresh()->paid_at;

    $this->travel(5)->minutes();
    postWebhook($completed())->assertOk();

    expect($domain->fresh()->sold_at->equalTo($soldAt))->toBeTrue()
        ->and($order->fresh()->paid_at->equalTo($paidAt))->toBeTrue()
        ->and(Order::count())->toBe(1);
});

it('rejects requests with a missing signature', function () use ($completed) {
    $order = pendingOrderFor(Domain::factory()->create());

    postWebhook($completed(), secret: null)->assertStatus(400);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('rejects requests signed with the wrong secret', function () use ($completed) {
    $order = pendingOrderFor(Domain::factory()->create());

    postWebhook($completed(), secret: 'whsec_wrong')->assertStatus(400);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('rejects replayed events outside the timestamp tolerance', function () use ($completed) {
    pendingOrderFor(Domain::factory()->create());

    postWebhook($completed(), timestamp: time() - 3600)->assertStatus(400);
});

it('rejects tampered payloads', function () use ($completed) {
    $order = pendingOrderFor(Domain::factory()->create());
    $payload = $completed();
    $t = time();
    $sig = "t=$t,v1=".hash_hmac('sha256', "$t.$payload", WEBHOOK_SECRET);

    test()->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sig], str_replace('pi_123', 'pi_evil', $payload))
        ->assertStatus(400);

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('does not fulfil unpaid completed sessions', function () use ($completed) {
    $domain = Domain::factory()->create();
    $order = pendingOrderFor($domain);

    postWebhook($completed('unpaid'))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($domain->fresh()->status)->toBe(DomainStatus::Live);
});

it('fails the order instead of double-selling a domain that was already taken', function () use ($completed) {
    $domain = Domain::factory()->create();
    $loser = pendingOrderFor($domain);
    $domain->forceFill(['status' => DomainStatus::Sold, 'sold_at' => now()->subDay()])->save();

    postWebhook($completed())->assertOk();

    expect($loser->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($loser->fresh()->failure_reason)->toContain($domain->name)
        ->and($domain->fresh()->sold_at->isYesterday())->toBeTrue();
});

it('marks the order failed when the checkout session expires', function () {
    $order = pendingOrderFor(Domain::factory()->create());

    postWebhook(stripeEvent('checkout.session.expired', ['id' => 'cs_test_abc']))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});

it('ignores unknown sessions and unrelated event types', function () {
    postWebhook(stripeEvent('checkout.session.completed', ['id' => 'cs_unknown', 'payment_status' => 'paid']))->assertOk();
    postWebhook(stripeEvent('customer.created', ['id' => 'cus_1']))->assertOk();

    expect(Order::count())->toBe(0);
});
