<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\QuickLink;
use App\Rules\SafeUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuickLinkRequest extends FormRequest
{
    use ValidatesTenantScope;

    public function authorize(): bool
    {
        return $this->user()->can('create', QuickLink::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => 'required|string',
            'link' => ['required', 'string', 'max:255', new SafeUrl],
            'lang' => ['nullable', 'string', Rule::in(config('app.locales'))],
            'icon' => 'nullable|string|max:125',
            'is_important' => 'boolean',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('quickLinks.create.padalinys')],
        ];
    }
}
