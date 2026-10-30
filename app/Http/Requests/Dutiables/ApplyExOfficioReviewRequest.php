<?php

namespace App\Http\Requests\Dutiables;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyExOfficioReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('managePeople', $this->route('duty'));
    }

    public function rules(): array
    {
        return [
            'fingerprint' => ['required', 'string', 'size:64'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'string', 'distinct'],
            'remainder_choices' => ['sometimes', 'array'],
            'remainder_choices.*' => ['required', Rule::in(['end', 'keep'])],
            'acknowledge_access_change' => ['nullable', 'boolean'],
        ];
    }
}
