<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'total_cents' => $this->total_cents,
            'failure_reason' => $this->failure_reason,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'domain_id' => $item->domain_id,
                'name' => $item->domain->name,
                'price_cents' => $item->price_cents,
            ])->all(),
        ];
    }
}
