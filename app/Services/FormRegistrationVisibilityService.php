<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Registration;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Builder;

class FormRegistrationVisibilityService
{
    public function __construct(
        private FormAccessService $formAccess,
        private FormSettings $formSettings,
        private AtstovavimasSettings $atstovavimasSettings,
    ) {}

    /**
     * @return Builder<Registration>
     */
    public function query(Form $form, User $user): Builder
    {
        $query = Registration::query()->whereBelongsTo($form);

        if ($this->formAccess->hasGlobalRead($user)) {
            return $query;
        }

        if ($this->formSettings->member_registration_form_id === $form->id) {
            return $this->scopeMemberRegistrations($query, $form, $user);
        }

        if ($this->formSettings->student_rep_registration_form_id === $form->id) {
            return $this->scopeStudentRepRegistrations($query, $form, $user);
        }

        return $query;
    }

    public function count(Form $form, User $user): int
    {
        return $this->query($form, $user)->count();
    }

    public function isSharedRegistrationForm(Form $form): bool
    {
        return $this->formSettings->member_registration_form_id === $form->id
            || $this->formSettings->student_rep_registration_form_id === $form->id;
    }

    /**
     * @param  Builder<Registration>  $query
     * @return Builder<Registration>
     */
    private function scopeMemberRegistrations(Builder $query, Form $form, User $user): Builder
    {
        $tenantIds = $this->formAccess->visibleTenantIds($user)
            ->merge($this->formSettings->getMemberRegistrationTenantIds($user))
            ->unique()
            ->values();

        abort_if($tenantIds->isEmpty(), 403, 'No member registration tenants to show.');

        $tenantField = $this->modelOptionsField($form, Tenant::class);

        return $this->scopeByFieldValues($query, $tenantField, $tenantIds);
    }

    /**
     * @param  Builder<Registration>  $query
     * @return Builder<Registration>
     */
    private function scopeStudentRepRegistrations(Builder $query, Form $form, User $user): Builder
    {
        $tenantIds = $this->formAccess->visibleTenantIds($user)
            ->merge($this->atstovavimasSettings->getManagerTenantIds($user))
            ->unique()
            ->values();

        abort_if($tenantIds->isEmpty(), 403, 'No student representative registration tenants to show.');

        $institutionField = $this->modelOptionsField($form, Institution::class);
        $institutionIds = Institution::query()
            ->whereIn('tenant_id', $tenantIds)
            ->pluck('id');

        return $this->scopeByFieldValues($query, $institutionField, $institutionIds);
    }

    private function modelOptionsField(Form $form, string $modelClass): FormField
    {
        $field = $form->formFields()
            ->where('use_model_options', true)
            ->where('options_model', $modelClass)
            ->first();

        abort_if($field === null, 403, 'The shared registration form is missing its tenant scope field.');

        return $field;
    }

    /**
     * @param  Builder<Registration>  $query
     * @param  iterable<int, int|string>  $values
     * @return Builder<Registration>
     */
    private function scopeByFieldValues(Builder $query, FormField $field, iterable $values): Builder
    {
        $valueStrings = collect($values)
            ->map(fn (int|string $value) => (string) $value)
            ->unique()
            ->values()
            ->all();

        return $query->whereHas('fieldResponses', function (Builder $responseQuery) use ($field, $valueStrings) {
            $responseQuery
                ->where('form_field_id', $field->id)
                ->whereIn('response->value', $valueStrings);
        });
    }
}
