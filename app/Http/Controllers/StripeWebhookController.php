<?php

namespace App\Http\Controllers;

use App\Exceptions\WebhookSignatureException;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\Contracts\PaymentGateway;
use App\Services\Checkout\PaymentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, PaymentGateway $gateway, CheckoutService $checkout): Response
    {
        try {
            $event = $gateway->parseWebhook($request->getContent(), $request->header('Stripe-Signature'));
        } catch (WebhookSignatureException) {
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            PaymentEvent::COMPLETED => $event->paid ? $checkout->markPaid($event->sessionId, $event->paymentIntentId) : null,
            PaymentEvent::EXPIRED => $checkout->markExpired($event->sessionId),
            default => null,
        };

        return response('ok');
    }
}
