<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Institution;
use App\Rules\SoftDeleteRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InstitutionRequest extends FormRequest
{
    use HasImageValidation;
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
            'working_hours.lt' => 'nullable|string|max:255',
            'working_hours.en' => 'nullable|string|max:255',
            'phone' => 'nullable|string',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
            'facebook_url' => 'nullable|string',
            'instagram_url' => 'nullable|string',
            'is_active' => 'boolean',
            'types' => 'nullable|array',
            'types.*' => ['integer', 'distinct', SoftDeleteRules::existsLive('institution_types')],
            'meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
            ...$this->imageMediaRules('image_media', $this->institution() ?? new Institution, 'image'),
            ...$this->imageMediaRules('logo_media', $this->institution() ?? new Institution, 'logo'),
        ];
    }

    private function institution(): ?Institution
    {
        $institution = $this->route('institution');

        return $institution instanceof Institution ? $institution : null;
    }
}
