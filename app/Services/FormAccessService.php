<?php

namespace App\Services;

use App\Models\Form;
use App\Models\User;
use App\Settings\AtstovavimasSettings;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FormAccessService
{
    public function __construct(
        private ModelAuthorizer $authorizer,
        private FormSettings $formSettings,
        private AtstovavimasSettings $atstovavimasSettings,
    ) {}

    public function canViewAny(User $user): bool
    {
        return $this->hasGlobalRead($user)
            || $this->hasTenantRead($user)
            || $this->viewableSpecialFormIds($user)->isNotEmpty();
    }

    public function canViewSpecialForm(User $user, Form $form): bool
    {
        if ($this->formSettings->member_registration_form_id === $form->id) {
            if ($this->formSettings->userIsMemberRegistrationRecipient($user)
                && $this->formSettings->getMemberRegistrationTenantIds($user)->isNotEmpty()) {
                return true;
            }

            return $this->hasTenantRead($user)
                && $this->visibleTenantIds($user)->isNotEmpty();
        }

        if ($this->formSettings->student_rep_registration_form_id === $form->id) {
            return $this->atstovavimasSettings->userIsInstitutionManager($user)
                && $this->atstovavimasSettings->getManagerTenantIds($user)->isNotEmpty();
        }

        return false;
    }

    /**
     * @return Collection<int, string>
     */
    public function viewableSpecialFormIds(User $user): Collection
    {
        $configuredIds = collect([
            $this->formSettings->member_registration_form_id,
            $this->formSettings->student_rep_registration_form_id,
        ])->filter()->unique()->values();

        if ($configuredIds->isEmpty()) {
            return collect();
        }

        return Form::query()
            ->whereKey($configuredIds)
            ->get()
            ->filter(fn (Form $form) => $this->canViewSpecialForm($user, $form))
            ->pluck('id')
            ->values();
    }

    /**
     * Apply the forms-index visibility boundary before pagination.
     *
     * @param  Builder<Form>  $query
     * @return Builder<Form>
     */
    public function applyIndexVisibility(Builder $query, User $user): Builder
    {
        if ($this->hasGlobalRead($user)) {
            return $query;
        }

        $tenantIds = $this->visibleTenantIds($user);
        $specialFormIds = $this->viewableSpecialFormIds($user);

        return $query->where(function (Builder $visibleQuery) use ($tenantIds, $specialFormIds) {
            if ($tenantIds->isNotEmpty()) {
                $visibleQuery->whereIn('tenant_id', $tenantIds);
            }

            if ($specialFormIds->isNotEmpty()) {
                $tenantIds->isNotEmpty()
                    ? $visibleQuery->orWhereIn('id', $specialFormIds)
                    : $visibleQuery->whereIn('id', $specialFormIds);
            }

            if ($tenantIds->isEmpty() && $specialFormIds->isEmpty()) {
                $visibleQuery->whereRaw('1 = 0');
            }
        });
    }

    public function hasGlobalRead(User $user): bool
    {
        return $user->isSuperAdmin()
            || $this->authorizer->forUser($user)->check('forms.read.*');
    }

    public function hasTenantRead(User $user): bool
    {
        return $this->authorizer->forUser($user)->check('forms.read.padalinys');
    }

    /**
     * @return Collection<int, int>
     */
    public function visibleTenantIds(User $user): Collection
    {
        if (! $this->hasTenantRead($user)) {
            return collect();
        }

        return $this->authorizer
            ->forUser($user)
            ->getTenants('forms.read.padalinys')
            ->pluck('id')
            ->unique()
            ->values();
    }
}
