<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ShowInstitutionCategoryRequest extends FormRequest
{
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('all');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Overrides the tenant default: www lists every padalinys, a subdomain only its own.
            'all' => ['nullable', 'boolean'],
        ];
    }
}
