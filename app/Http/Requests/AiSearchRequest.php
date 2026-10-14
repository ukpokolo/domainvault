<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }

    public function searchText(): string
    {
        return $this->validated('query');
    }
}
