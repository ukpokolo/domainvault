<?php

namespace App\Services\Search;

use App\DTOs\DomainFilters;

/** Builds the removable "chips" that describe the active filters. */
class FilterChips
{
    /** @return list<array{key: string, label: string, remove_url: string}> */
    public function for(DomainFilters $filters, ?string $ai = null): array
    {
        $labels = [
            'q' => fn ($v) => "Keyword: {$v}",
            'tld' => fn ($v) => ".{$v}",
            'niche' => fn ($v) => $v,
            'min_age' => fn ($v) => "{$v}+ years old",
            'max_price' => fn ($v) => 'Under $'.number_format((int) $v),
            'min_authority' => fn ($v) => "Authority {$v}+",
        ];

        $chips = [];
        foreach ($filters->toArray() as $key => $value) {
            if (! isset($labels[$key])) {
                continue; // sort is not a filter chip
            }

            $params = $filters->without($key)->toArray() + array_filter(['ai' => $ai]);
            $chips[] = [
                'key' => $key,
                'label' => $labels[$key]($value),
                'remove_url' => route('marketplace.index', $params),
            ];
        }

        return $chips;
    }
}
