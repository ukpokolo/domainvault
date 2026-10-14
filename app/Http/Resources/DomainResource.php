<?php

namespace App\Http\Resources;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Domain */
class DomainResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'tld' => $this->tld,
            'registered_year' => $this->registered_year,
            'age' => $this->age,
            'backlinks' => $this->backlinks,
            'referring_domains' => $this->referring_domains,
            'authority_score' => $this->authority_score,
            'niche' => $this->niche,
            'price_cents' => $this->price_cents,
            'price' => $this->price,
            'status' => $this->status->value,
            'notes' => $this->notes,
        ];
    }
}
