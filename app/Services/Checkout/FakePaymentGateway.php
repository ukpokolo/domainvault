<?php

namespace App\Services\Checkout;

use App\Models\Order;
use App\Services\Checkout\Contracts\PaymentGateway;

/** Test double: records created sessions and never touches the network. */
class FakePaymentGateway implements PaymentGateway
{
    /** @var list<Order> */
    public array $sessions = [];

    public ?PaymentEvent $nextEvent = null;

    public function createSession(Order $order, string $successUrl, string $cancelUrl): PaymentSession
    {
        $this->sessions[] = $order;
        $id = 'cs_test_fake_'.$order->id;

        return new PaymentSession($id, "https://checkout.stripe.test/pay/{$id}");
    }

    public function parseWebhook(string $payload, ?string $signatureHeader): PaymentEvent
    {
        return $this->nextEvent ?? new PaymentEvent(PaymentEvent::IGNORED);
    }
}
