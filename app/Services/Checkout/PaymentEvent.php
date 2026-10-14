<?php

namespace App\Services\Checkout;

final readonly class PaymentEvent
{
    public const COMPLETED = 'completed';

    public const EXPIRED = 'expired';

    public const IGNORED = 'ignored';

    public function __construct(
        public string $type,
        public ?string $sessionId = null,
        public ?string $paymentIntentId = null,
        public bool $paid = false,
    ) {}
}
