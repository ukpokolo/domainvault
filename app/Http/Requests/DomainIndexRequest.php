<?php

namespace App\Http\Requests;

use App\DTOs\DomainFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DomainIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'tld' => ['nullable', 'string', 'max:20'],
            'niche' => ['nullable', 'string', 'max:60'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:40'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'min_authority' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort' => ['nullable', Rule::in(DomainFilters::SORTS)],
        ];
    }

    public function filters(): DomainFilters
    {
        return DomainFilters::fromArray($this->validated());
    }
}
