<?php

use App\Enums\DomainStatus;
use App\Enums\OrderStatus;
use App\Models\CartItem;
use App\Models\Domain;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\Contracts\PaymentGateway;
use App\Services\Checkout\FakePaymentGateway;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->gateway = new FakePaymentGateway;
    app()->instance(PaymentGateway::class, $this->gateway);
    $this->buyer = User::factory()->create();
});

it('requires authentication for the cart and checkout', function () {
    $domain = Domain::factory()->create();

    $this->get('/cart')->assertRedirect('/login');
    $this->post("/cart/{$domain->id}")->assertRedirect('/login');
    $this->post('/checkout')->assertRedirect('/login');
    $this->get('/orders')->assertRedirect('/login');
});

it('adds a live domain to the cart once', function () {
    $domain = Domain::factory()->create();

    $this->actingAs($this->buyer)->post("/cart/{$domain->id}")->assertSessionHas('success');
    $this->actingAs($this->buyer)->post("/cart/{$domain->id}");

    expect(CartItem::count())->toBe(1);
});

it('refuses to add unavailable domains', function () {
    $pending = Domain::factory()->pending()->create();
    $sold = Domain::factory()->sold()->create();

    $this->actingAs($this->buyer)->post("/cart/{$pending->id}")->assertSessionHas('error');
    $this->actingAs($this->buyer)->post("/cart/{$sold->id}")->assertSessionHas('error');

    expect(CartItem::count())->toBe(0);
});

it('lists the cart with its total and removes items', function () {
    $a = Domain::factory()->create(['price_cents' => 10_000]);
    $b = Domain::factory()->create(['price_cents' => 25_000]);
    $this->buyer->cartItems()->createMany([['domain_id' => $a->id], ['domain_id' => $b->id]]);

    $this->actingAs($this->buyer)->get('/cart')->assertInertia(fn (Assert $p) => $p
        ->component('Cart')->has('items', 2)->where('total_cents', 35_000));

    $this->actingAs($this->buyer)->delete("/cart/{$a->id}");
    expect(CartItem::count())->toBe(1);
});

it('creates a pending order with price snapshots and redirects to the payment page', function () {
    $a = Domain::factory()->create(['price_cents' => 10_000]);
    $b = Domain::factory()->create(['price_cents' => 25_000]);
    $this->buyer->cartItems()->createMany([['domain_id' => $a->id], ['domain_id' => $b->id]]);

    $response = $this->actingAs($this->buyer)->post('/checkout', [], ['X-Inertia' => 'true']);

    $order = Order::with('items')->sole();
    $payUrl = "https://checkout.stripe.test/pay/cs_test_fake_{$order->id}";
    // Inertia requests get a 409 + location header so the browser leaves the SPA.
    $response->assertStatus(409)->assertHeader('X-Inertia-Location', $payUrl);
    expect($order->status)->toBe(OrderStatus::Pending)
        ->and($order->total_cents)->toBe(35_000)
        ->and($order->stripe_session_id)->toBe("cs_test_fake_{$order->id}")
        ->and($order->items->pluck('price_cents')->sort()->values()->all())->toBe([10_000, 25_000]);

    // Nothing is sold until the webhook confirms payment.
    expect($a->fresh()->status)->toBe(DomainStatus::Live);
});

it('blocks checkout of an empty cart', function () {
    $this->actingAs($this->buyer)->post('/checkout')->assertRedirect('/cart')->assertSessionHas('error');

    expect(Order::count())->toBe(0);
});

it('removes domains that were sold meanwhile and does not create an order', function () {
    $gone = Domain::factory()->create();
    $this->buyer->cartItems()->create(['domain_id' => $gone->id]);
    $gone->update(['status' => DomainStatus::Sold]);

    $this->actingAs($this->buyer)->post('/checkout')->assertRedirect('/cart')->assertSessionHas('error');

    expect(Order::count())->toBe(0)->and(CartItem::count())->toBe(0);
});

it('shows only the buyer their own orders', function () {
    $mine = Order::factory()->for($this->buyer)->create();
    $theirs = Order::factory()->for(User::factory())->create();

    $this->actingAs($this->buyer)->get('/orders')->assertInertia(fn (Assert $p) => $p
        ->component('Orders/Index')->has('orders.data', 1)->where('orders.data.0.id', $mine->id));

    $this->actingAs($this->buyer)->get("/orders/{$mine->id}")->assertOk();
    $this->actingAs($this->buyer)->get("/orders/{$theirs->id}")->assertForbidden();
});
