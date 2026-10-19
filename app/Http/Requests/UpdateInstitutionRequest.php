<?php

namespace App\Http\Requests;

use App\Actions\GenerateInstitutionAlias;
use App\Models\Tenant;
use App\Services\InstitutionDeactivationService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInstitutionRequest extends InstitutionRequest
{
    /**
     * A rename carries an alias the editor left untouched along with it; the old address keeps
     * redirecting (Institution::booted()). Pinned aliases never move.
     */
    #[\Override]
    public function prepareForValidation(): void
    {
        $institution = $this->institution;

        if (GenerateInstitutionAlias::isPinned($institution) || ! $this->has('alias')) {
            $this->merge(['alias' => $institution->alias]);

            return;
        }

        $name = (string) ($this->input('name.lt') ?: $institution->getTranslation('name', 'lt'));
        $renamed = $name !== $institution->getTranslation('name', 'lt', false);
        $alias = $this->input('alias');

        if (blank($alias) || ($renamed && $alias === $institution->alias)) {
            $tenant = Tenant::query()->find($this->input('tenant_id', $institution->tenant_id));

            $this->merge(['alias' => GenerateInstitutionAlias::execute($name, $tenant, $institution->getKey())]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isEmpty() && $this->has('is_active') && ! $this->boolean('is_active') && $this->institution->is_active) {
                if (app(InstitutionDeactivationService::class)->summary($this->institution)['blockers'] !== []) {
                    $validator->errors()->add('is_active', __('forms.helpers.institution_deactivation_blocked'));
                }
            }
        });
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->institution);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // Compared on the Lithuanian value: the columns are JSON, so a bare column name never matches.
            // No database unique index backs this, so a soft-deleted record must not reserve the value.
            'name.lt' => ['required', Rule::unique('institutions', 'name->lt')->ignore($this->institution->id)->withoutTrashed()],
            'short_name.lt' => ['nullable', Rule::unique('institutions', 'short_name->lt')->ignore($this->institution->id)->withoutTrashed()],
            'alias' => $this->aliasRules(),
        ]);
    }
}
