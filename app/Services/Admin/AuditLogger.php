<?php

namespace App\Services\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /** @param array<string, mixed> $meta */
    public function log(User $actor, string $action, ?Model $subject = null, array $meta = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
        ]);
    }
}
