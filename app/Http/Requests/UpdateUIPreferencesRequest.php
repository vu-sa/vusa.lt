<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUIPreferencesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sidebar' => ['nullable', 'array'],
            'sidebar.sections' => ['nullable', 'array'],
            'sidebar.sections.*' => ['boolean'],
            'sidebar.order' => ['nullable', 'array'],
            'sidebar.order.*' => ['string'],
            'sidebar.collapsed' => ['nullable', 'boolean'],
            'quick_actions' => ['nullable', 'array'],
            'quick_actions.*' => ['boolean'],
            'appearance' => ['nullable', 'array'],
            'appearance.density' => ['nullable', Rule::in(['comfortable', 'compact'])],
            'pinned_pages' => ['nullable', 'array'],
            'pinned_pages.*.route' => ['required_with:pinned_pages', 'string'],
            'pinned_pages.*.params' => ['nullable', 'array'],
            'pinned_pages.*.title' => ['nullable', 'string', 'max:255'],
            'pinned_pages.*.url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
