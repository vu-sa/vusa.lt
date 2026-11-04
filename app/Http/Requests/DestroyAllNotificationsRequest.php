<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DestroyAllNotificationsRequest extends FormRequest
{
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('read_only');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'read_only' => ['nullable', 'boolean'],
        ];
    }
}
