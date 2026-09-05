<?php

namespace App\Http\Requests;

use App\Models\Banner;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Banner::class);
    }

    /**
     * The tenant is resolved from the acting user's permissions in the controller, not from
     * the payload, so there is no tenant_id rule here.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            // A logo is optional: with none, the public banner renders the title as a text mark.
            'image_url' => 'nullable|string|max:255',
            // Both are persisted by the controller and used to have no rule at all.
            'link_url' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ];
    }
}
