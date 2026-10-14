<?php

namespace App\Models;

use App\Enums\DomainStatus;
use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Domain extends Model
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory, Searchable;

    protected $fillable = [
        'name', 'tld', 'registered_year', 'backlinks', 'referring_domains',
        'authority_score', 'niche', 'price_cents', 'status', 'notes', 'seller_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => DomainStatus::class,
            'registered_year' => 'integer',
            'backlinks' => 'integer',
            'referring_domains' => 'integer',
            'authority_score' => 'integer',
            'price_cents' => 'integer',
            'sold_at' => 'datetime',
        ];
    }

    /** Age in whole years, computed from the registration year. */
    protected function age(): Attribute
    {
        return Attribute::get(fn () => max(0, now()->year - $this->registered_year));
    }

    protected function price(): Attribute
    {
        return Attribute::get(fn () => $this->price_cents / 100);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function scopeLive(Builder $query): void
    {
        $query->where('status', DomainStatus::Live);
    }

    public function scopeWithStatus(Builder $query, DomainStatus $status): void
    {
        $query->where('status', $status);
    }

    /** Domains at least $years old (registered in or before the cutoff year). */
    public function scopeMinAge(Builder $query, int $years): void
    {
        $query->where('registered_year', '<=', now()->year - $years);
    }

    public function isPurchasable(): bool
    {
        return $this->status === DomainStatus::Live;
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'niche' => $this->niche,
            'notes' => $this->notes,
        ];
    }
}
