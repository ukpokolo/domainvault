<?php

namespace App\Http\Requests;

use App\Enums\DomainStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeDomainStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Sold can never be set by hand.
            'status' => ['required', Rule::enum(DomainStatus::class)->except(DomainStatus::Sold)],
        ];
    }

    public function status(): DomainStatus
    {
        return DomainStatus::from($this->validated('status'));
    }
}
