<?php

namespace App\Services\Checkout\Contracts;

use App\Exceptions\WebhookSignatureException;
use App\Models\Order;
use App\Services\Checkout\PaymentEvent;
use App\Services\Checkout\PaymentSession;

interface PaymentGateway
{
    /** Create a hosted checkout session for the order and return where to send the buyer. */
    public function createSession(Order $order, string $successUrl, string $cancelUrl): PaymentSession;

    /**
     * Verify a webhook delivery and normalise it.
     *
     * @throws WebhookSignatureException when the signature or payload is invalid
     */
    public function parseWebhook(string $payload, ?string $signatureHeader): PaymentEvent;
}
