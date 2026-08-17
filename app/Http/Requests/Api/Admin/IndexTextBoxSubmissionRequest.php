<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\ValidationRule;

class IndexTextBoxSubmissionRequest extends ContentPartScopedRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
    }

    public function getPerPage(): int
    {
        return (int) ($this->validated('per_page') ?? 20);
    }
}
