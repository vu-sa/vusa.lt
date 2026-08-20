<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuickLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('quickLink'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'link' => 'required|string|max:255',
            'lang' => ['nullable', 'string', Rule::in(config('app.locales'))],
            'icon' => 'nullable|string|max:125',
            'is_important' => 'boolean',
        ];
    }
}
