<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Form;
use App\Models\User;
use App\Services\FormAccessService;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    protected bool $hasManyTenants = false;

    public function __construct(
        ModelAuthorizer $authorizer,
        private FormAccessService $formAccess,
    ) {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::FORM()->label);
    }

    public function viewAny(User $user): bool
    {
        return $this->formAccess->canViewAny($user);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  Form  $form
     */
    public function view(User $user, Model $form): bool
    {
        if ($this->commonChecker($user, $form, CRUDEnum::READ()->label, $this->pluralModelName, false)) {
            return true;
        }

        return $this->canViewSpecialForm($user, $form);
    }

    /**
     * The member and student rep registration forms belong to the central tenant, so the
     * ordinary tenant check never passes for padalinys staff who nevertheless own the
     * process. Access for those two forms is driven by the role settings that already
     * define who handles each registration type.
     *
     * @param  Form  $form
     */
    public function canViewSpecialForm(User $user, Model $form): bool
    {
        return $this->formAccess->canViewSpecialForm($user, $form);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Form  $form
     */
    public function update(User $user, Model $form): bool
    {
        return $this->commonChecker($user, $form, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  Form  $form
     */
    public function delete(User $user, Model $form): bool
    {
        return $this->commonChecker($user, $form, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
