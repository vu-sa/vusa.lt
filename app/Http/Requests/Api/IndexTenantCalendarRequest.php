<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexTenantCalendarRequest extends FormRequest
{
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('all_tenants');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lang' => ['nullable', 'in:lt,en'],
            'all_tenants' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
