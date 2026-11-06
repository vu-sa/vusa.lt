<?php

namespace App\Http\Requests;

use App\Actions\GenerateInstitutionAlias;
use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Institution;
use App\Rules\SoftDeleteRules;
use App\Rules\UniqueAmongTrashed;
use App\Services\PublicUrlService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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

    /**
     * An alias still redirecting for another institution is taken too, or that old address
     * would start opening this one.
     *
     * @return array<int, mixed>
     */
    protected function aliasRules(): array
    {
        $ignoreId = $this->institution()?->getKey();

        return [
            'required',
            'string',
            'max:255',
            'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            Rule::notIn(GenerateInstitutionAlias::reserved()),
            UniqueAmongTrashed::of('institutions', 'alias')->ignore($ignoreId),
            function (string $attribute, mixed $value, Closure $fail) use ($ignoreId): void {
                $owner = app(PublicUrlService::class)->resolveInstitutionAlias((string) $value)?->urlable_id;

                if ($owner !== null && $owner !== $ignoreId) {
                    $fail('validation.unique')->translate(['attribute' => 'alias']);
                }
            },
        ];
    }

    protected function institution(): ?Institution
    {
        $institution = $this->route('institution');

        return $institution instanceof Institution ? $institution : null;
    }
}
