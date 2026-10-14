<?php

namespace App\Services\Search;

use App\DTOs\DomainFilters;
use App\Models\Domain;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class DomainSearchService
{
    public function paginate(DomainFilters $filters, int $perPage = 12): LengthAwarePaginator
    {
        // Free text goes through Scout (database driver by default, Meilisearch optionally);
        // structured filters are applied to the underlying Eloquent query in both cases.
        if ($filters->q !== null) {
            return Domain::search($filters->q)
                ->query(fn (Builder $query) => $this->apply($query, $filters))
                ->paginate($perPage);
        }

        return $this->apply(Domain::query(), $filters)->paginate($perPage);
    }

    public function apply(Builder $query, DomainFilters $filters): Builder
    {
        $query->live();

        if ($filters->tld !== null) {
            $query->where('tld', $filters->tld);
        }
        if ($filters->niche !== null) {
            $query->where('niche', $filters->niche);
        }
        if ($filters->minAge !== null) {
            $query->minAge($filters->minAge);
        }
        if ($filters->maxPrice !== null) {
            $query->where('price_cents', '<=', $filters->maxPrice * 100);
        }
        if ($filters->minAuthority !== null) {
            $query->where('authority_score', '>=', $filters->minAuthority);
        }

        return $this->sort($query, $filters->sort);
    }

    private function sort(Builder $query, string $sort): Builder
    {
        match ($sort) {
            'price_asc' => $query->orderBy('price_cents'),
            'price_desc' => $query->orderByDesc('price_cents'),
            'authority_desc' => $query->orderByDesc('authority_score'),
            'age_desc' => $query->orderBy('registered_year'),
            'backlinks_desc' => $query->orderByDesc('backlinks'),
            default => $query->latest('id'),
        };

        // Stable tie-breaker so pagination never duplicates or skips rows.
        return $sort === 'newest' ? $query : $query->orderBy('id');
    }

    /** Values for the filter dropdowns (live inventory only). @return array{tlds: list<string>, niches: list<string>} */
    public function facets(): array
    {
        return Cache::remember('domains.facets', 300, fn () => [
            'tlds' => Domain::live()->distinct()->orderBy('tld')->pluck('tld')->all(),
            'niches' => Domain::live()->distinct()->orderBy('niche')->pluck('niche')->all(),
        ]);
    }
}
