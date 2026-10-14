<?php

namespace App\Http\Requests;

use App\DTOs\DomainFilters;
use Illuminate\Foundation\Http\FormRequest;

class ApiDomainIndexRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            ...DomainFilters::rules(),
        ];
    }

    public function filters(): DomainFilters
    {
        return DomainFilters::fromArray($this->validated());
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }
}
