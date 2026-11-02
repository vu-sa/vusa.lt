<?php

namespace App\Http\Requests\Comments;

use App\Http\Requests\Concerns\NormalizesBooleanFlags;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexCommentsRequest extends FormRequest
{
    use NormalizesBooleanFlags;

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $this->normalizeBooleanFlags('resolved');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resolved' => ['nullable', 'boolean'],
        ];
    }
}
