<?php

namespace App\Services\Checkout;

final readonly class PaymentSession
{
    public function __construct(public string $id, public string $url) {}
}
