<?php

namespace App\Services\Admin;

use App\Enums\DomainStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ListingReviewService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function approve(User $admin, Domain $domain): Domain
    {
        return $this->transition($admin, $domain, DomainStatus::Live, 'domain.approved');
    }

    public function reject(User $admin, Domain $domain, ?string $reason = null): Domain
    {
        // Rejected listings go back to draft so the seller can fix and resubmit.
        return $this->transition($admin, $domain, DomainStatus::Draft, 'domain.rejected', ['reason' => $reason]);
    }

    public function changeStatus(User $admin, Domain $domain, DomainStatus $to): Domain
    {
        return $this->transition($admin, $domain, $to, 'domain.status_changed');
    }

    /** @param array<string, mixed> $meta */
    private function transition(User $admin, Domain $domain, DomainStatus $to, string $action, array $meta = []): Domain
    {
        return DB::transaction(function () use ($admin, $domain, $to, $action, $meta) {
            $domain = Domain::lockForUpdate()->findOrFail($domain->id);
            $from = $domain->status;

            if (! $from->canTransitionTo($to)) {
                throw new InvalidStatusTransition("Cannot move a {$from->value} domain to {$to->value}.");
            }

            $domain->update(['status' => $to]);
            $this->audit->log($admin, $action, $domain, ['from' => $from->value, 'to' => $to->value] + array_filter($meta));

            return $domain;
        });
    }
}
