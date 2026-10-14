<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __invoke(): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        return Inertia::render('Admin/AuditLog', [
            'logs' => JsonResource::collection(AuditLog::with(['user:id,name', 'auditable'])->latest('id')->paginate(25)->through(fn (AuditLog $l) => [
                'id' => $l->id,
                'action' => $l->action,
                'actor' => $l->user?->name ?? 'System',
                'subject' => $l->auditable ? ($l->auditable->name ?? $l->auditable->original_name ?? class_basename($l->auditable).' #'.$l->auditable->getKey()) : null,
                'meta' => $l->meta,
                'created_at' => $l->created_at->toIso8601String(),
            ])),
        ]);
    }
}
