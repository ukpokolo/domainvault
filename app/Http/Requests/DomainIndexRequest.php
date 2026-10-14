<?php

namespace App\Http\Requests;

use App\DTOs\DomainFilters;
use Illuminate\Foundation\Http\FormRequest;

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
            'ai' => ['nullable', 'string', 'max:200'],
            ...DomainFilters::rules(),
        ];
    }

    public function filters(): DomainFilters
    {
        return DomainFilters::fromArray($this->validated());
    }
}
