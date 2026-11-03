<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\DropsInvalidOptionalQuery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexPublicSearchRequest extends FormRequest
{
    use DropsInvalidOptionalQuery;

    public function authorize(): bool
    {
        return true;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('q'))) {
            $this->merge(['q' => mb_substr($this->input('q'), 0, 200)]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
        ];
    }
}
