<?php

namespace App\Http\Requests\Dutiables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewExOfficioRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['manual', 'convert', 'detach'])],
            'source_id' => ['required_if:decision,convert', 'nullable', 'ulid'],
            'acknowledge' => ['accepted'],
            'acknowledge_access_change' => ['nullable', 'boolean'],
        ];
    }
}
