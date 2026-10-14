<?php

namespace App\Services\Checkout;

use App\Exceptions\CheckoutException;
use App\Exceptions\WebhookSignatureException;
use App\Models\Order;
use App\Services\Checkout\Contracts\PaymentGateway;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class StripePaymentGateway implements PaymentGateway
{
    private ?StripeClient $client = null;

    public function __construct(
        private readonly ?string $secretKey,
        private readonly ?string $webhookSecret,
    ) {}

    public function createSession(Order $order, string $successUrl, string $cancelUrl): PaymentSession
    {
        if (! $this->secretKey) {
            throw new CheckoutException('Stripe is not configured (STRIPE_SECRET).');
        }

        $order->loadMissing('items.domain');

        try {
            $session = $this->client()->checkout->sessions->create([
                'mode' => 'payment',
                'client_reference_id' => (string) $order->id,
                'customer_email' => $order->user?->email,
                'metadata' => ['order_id' => (string) $order->id],
                'line_items' => $order->items->map(fn ($item) => [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => 'usd',
                        'unit_amount' => $item->price_cents,
                        'product_data' => ['name' => $item->domain->name],
                    ],
                ])->all(),
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ], ['idempotency_key' => "order-{$order->id}"]);
        } catch (ApiErrorException $e) {
            throw new CheckoutException('Could not start the payment session.', previous: $e);
        }

        return new PaymentSession($session->id, $session->url);
    }

    public function parseWebhook(string $payload, ?string $signatureHeader): PaymentEvent
    {
        if (! $this->webhookSecret || ! $signatureHeader) {
            throw new WebhookSignatureException('Missing webhook secret or signature.');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $this->webhookSecret);
        } catch (SignatureVerificationException|UnexpectedValueException $e) {
            throw new WebhookSignatureException('Invalid webhook signature.', previous: $e);
        }

        $object = $event->data->object;

        return match ($event->type) {
            'checkout.session.completed',
            'checkout.session.async_payment_succeeded' => new PaymentEvent(
                PaymentEvent::COMPLETED,
                $object->id,
                is_string($object->payment_intent ?? null) ? $object->payment_intent : null,
                ($object->payment_status ?? null) === 'paid',
            ),
            'checkout.session.expired',
            'checkout.session.async_payment_failed' => new PaymentEvent(PaymentEvent::EXPIRED, $object->id),
            default => new PaymentEvent(PaymentEvent::IGNORED),
        };
    }

    private function client(): StripeClient
    {
        return $this->client ??= new StripeClient($this->secretKey);
    }
}
