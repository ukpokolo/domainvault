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
}
