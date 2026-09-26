<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MergeCandidatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['type' => $this->route('type')]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['users', 'duties', 'study-programs', 'tags'])],
            'source_ids' => ['required', 'array', 'min:1', 'max:20'],
            'source_ids.*' => ['required', 'string', 'distinct'],
            'query' => ['required', 'string', 'min:2', 'max:100'],
        ];
    }
}
