<?php

namespace App\Http\Requests;

use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuickLinkOrderRequest extends FormRequest
{
    /**
     * Each row is authorized individually in the controller, because reordering may span
     * several quick links and the ability is checked per record.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'orderList' => 'required|array',
            // The controller dereferences both keys; without rules a malformed row threw
            // instead of reporting a validation error.
            'orderList.*.id' => ['required', SoftDeleteRules::existsLive('quick_links')],
            'orderList.*.order' => 'required|integer|min:0',
            // Read after the reorder to build the redirect back to the right listing.
            'tenant_id' => 'nullable|integer|exists:tenants,id',
            'lang' => ['nullable', 'string', Rule::in(config('app.locales'))],
        ];
    }
}
