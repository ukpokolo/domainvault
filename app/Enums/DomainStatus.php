<?php

namespace App\Enums;

enum DomainStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Live = 'live';
    case Sold = 'sold';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Statuses an admin may move a listing to by hand. Sold is terminal and is
     * only ever set by a confirmed payment.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Pending, self::Live],
            self::Pending => [self::Live, self::Draft],
            self::Live => [self::Draft, self::Pending],
            self::Sold => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }
}
