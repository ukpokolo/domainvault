<?php

namespace App\DTOs;

use Illuminate\Validation\Rule;

/**
 * Normalised domain search filters. The single shape shared by the web UI,
 * the AI search interpreter and the REST API.
 */
final readonly class DomainFilters
{
    public const SORTS = ['newest', 'price_asc', 'price_desc', 'authority_desc', 'age_desc', 'backlinks_desc'];

    public function __construct(
        public ?string $q = null,
        public ?string $tld = null,
        public ?string $niche = null,
        public ?int $minAge = null,
        public ?int $maxPrice = null,
        public ?int $minAuthority = null,
        public string $sort = 'newest',
    ) {}

    /**
     * Validation rules for every filter field; the single source of truth for
     * the web form request, the API and the LLM output validator.
     *
     * @return array<string, list<mixed>>
     */
    public static function rules(): array
    {
        return [
            'tld' => ['nullable', 'string', 'max:20', 'regex:/^\.?[A-Za-z0-9]+$/'],
            'niche' => ['nullable', 'string', 'max:60'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:40'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'min_authority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort' => ['nullable', Rule::in(self::SORTS)],
        ];
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $int = fn (string $k): ?int => isset($data[$k]) && $data[$k] !== '' ? (int) $data[$k] : null;
        $str = fn (string $k): ?string => isset($data[$k]) && trim((string) $data[$k]) !== '' ? trim((string) $data[$k]) : null;
        $sort = $str('sort');

        return new self(
            q: $str('q'),
            tld: ($tld = $str('tld')) ? ltrim(strtolower($tld), '.') : null,
            niche: $str('niche'),
            minAge: $int('min_age'),
            maxPrice: $int('max_price'),
            minAuthority: $int('min_authority'),
            sort: in_array($sort, self::SORTS, true) ? $sort : 'newest',
        );
    }

    /** Only the filters that are set, keyed like the query string. @return array<string, string|int> */
    public function toArray(): array
    {
        return array_filter([
            'q' => $this->q,
            'tld' => $this->tld,
            'niche' => $this->niche,
            'min_age' => $this->minAge,
            'max_price' => $this->maxPrice,
            'min_authority' => $this->minAuthority,
            'sort' => $this->sort === 'newest' ? null : $this->sort,
        ], fn ($v) => $v !== null);
    }

    public function with(array $overrides): self
    {
        return self::fromArray([...$this->toArray(), ...$overrides]);
    }

    public function without(string $key): self
    {
        $data = $this->toArray();
        unset($data[$key]);

        return self::fromArray($data);
    }
}
