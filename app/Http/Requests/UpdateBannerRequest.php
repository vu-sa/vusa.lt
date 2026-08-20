<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('banner'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'image_url' => 'required|string|max:255',
            // Both are persisted by the controller and used to have no rule at all.
            'link_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
