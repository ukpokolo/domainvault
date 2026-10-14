<?php

namespace App\Services\Checkout;

use App\Enums\DomainStatus;
use App\Enums\OrderStatus;
use App\Exceptions\CheckoutException;
use App\Models\CartItem;
use App\Models\Domain;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Services\Checkout\Contracts\PaymentGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Turn the buyer's cart into a pending order and open a hosted payment session.
     *
     * @throws CheckoutException if the cart is empty or a domain was sold/withdrawn meanwhile
     */
    public function start(User $user, string $successUrl, string $cancelUrl): PaymentSession
    {
        $items = $this->cart->items($user);

        if ($items->isEmpty()) {
            throw new CheckoutException('Your cart is empty.');
        }

        // Re-check availability against fresh data; carts can be days old.
        $unavailable = $items->filter(fn (CartItem $i) => ! $i->domain->isPurchasable());
        if ($unavailable->isNotEmpty()) {
            $unavailable->each(fn (CartItem $i) => $i->delete());

            throw new CheckoutException(
                $unavailable->map(fn (CartItem $i) => $i->domain->name)->join(', ').' is no longer available and was removed from your cart.'
            );
        }

        $order = DB::transaction(function () use ($user, $items) {
            $order = $user->orders()->create([
                'status' => OrderStatus::Pending,
                'total_cents' => $this->cart->totalCents($items),
            ]);

            $order->items()->createMany($items->map(fn (CartItem $i) => [
                'domain_id' => $i->domain_id,
                'price_cents' => $i->domain->price_cents,
            ])->all());

            return $order;
        });

        $session = $this->gateway->createSession(
            $order,
            str_replace('{ORDER}', (string) $order->id, $successUrl),
            $cancelUrl,
        );

        $order->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    /**
     * Fulfil an order after a confirmed payment. Safe to call repeatedly (webhooks are retried).
     */
    public function markPaid(string $sessionId, ?string $paymentIntentId = null): ?Order
    {
        return DB::transaction(function () use ($sessionId, $paymentIntentId) {
            $order = Order::where('stripe_session_id', $sessionId)->lockForUpdate()->first();

            if (! $order) {
                Log::warning('Payment received for unknown checkout session', ['session' => $sessionId]);

                return null;
            }

            if ($order->status !== OrderStatus::Pending) {
                return $order; // already handled: idempotent no-op
            }

            $domainIds = $order->items()->pluck('domain_id');
            $domains = Domain::whereIn('id', $domainIds)->lockForUpdate()->get();

            $taken = $domains->filter(fn (Domain $d) => $d->status !== DomainStatus::Live);
            if ($taken->isNotEmpty()) {
                // Two buyers paid for the same unique asset; the loser needs a manual refund.
                $order->update([
                    'status' => OrderStatus::Failed,
                    'stripe_payment_intent' => $paymentIntentId,
                    'failure_reason' => 'Domain no longer available: '.$taken->pluck('name')->join(', '),
                ]);
                Log::warning('Paid order could not be fulfilled; refund required', ['order' => $order->id]);

                return $order;
            }

            Domain::whereIn('id', $domainIds)->update([
                'status' => DomainStatus::Sold,
                'sold_at' => now(),
            ]);

            // Remove the sold domains from every cart, not just the buyer's.
            CartItem::whereIn('domain_id', $domainIds)->delete();

            $order->update([
                'status' => OrderStatus::Paid,
                'paid_at' => now(),
                'stripe_payment_intent' => $paymentIntentId,
            ]);

            return $order;
        });
    }

    public function markExpired(string $sessionId): void
    {
        Order::where('stripe_session_id', $sessionId)
            ->where('status', OrderStatus::Pending)
            ->update(['status' => OrderStatus::Failed, 'failure_reason' => 'Checkout session expired']);
    }
}
