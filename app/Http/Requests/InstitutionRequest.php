<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InstitutionRequest extends FormRequest
{
    use ValidatesTenantScope;

    /**
     * The permission whose tenant scope constrains `tenant_id`. Store and Update override it
     * so each uses its own scope.
     */
    protected string $tenantScopePermission = 'institutions.update.padalinys';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name.lt' => 'required',
            'short_name.lt' => 'nullable',
            'description.lt' => 'nullable',
            'name.en' => 'nullable',
            'short_name.en' => 'nullable',
            'description.en' => 'nullable',
            'address.lt' => 'nullable|string',
            'address.en' => 'nullable|string',
            'website' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
            'image_url' => 'nullable|string',
            'logo_url' => 'nullable|string',
            'facebook_url' => 'nullable|string',
            'instagram_url' => 'nullable|string',
            'is_active' => 'boolean',
            'types' => 'nullable|array',
            'meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
        ];
    }
}
