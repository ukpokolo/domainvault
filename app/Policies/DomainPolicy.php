<?php

namespace App\Policies;

use App\Enums\DomainStatus;
use App\Models\Domain;
use App\Models\User;

class DomainPolicy
{
    /** Live and sold listings are public; everything else is admin-only. */
    public function view(?User $user, Domain $domain): bool
    {
        return in_array($domain->status, [DomainStatus::Live, DomainStatus::Sold], true)
            || (bool) $user?->isAdmin();
    }

    public function review(User $user, Domain $domain): bool
    {
        return $user->isAdmin();
    }

    public function updateStatus(User $user, Domain $domain): bool
    {
        return $user->isAdmin();
    }
}
