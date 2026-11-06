<?php

namespace App\Http\Requests\Dutiables;

use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyDutiableRequest extends FormRequest
{
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return $this->user()?->can('manageDutiable', $this->route('dutiable')) ?? false;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('stay', 'acknowledge_access_change');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // The timeline deletes rows in place and must not be navigated away from.
            'stay' => ['nullable', 'boolean'],
            'acknowledge_access_change' => ['nullable', 'boolean'],
        ];
    }
}
