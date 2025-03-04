<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Form;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Settings\FormSettings;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class FormPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::FORM()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, Form $form)
    {
        if ($this->commonChecker($user, $form, CRUDEnum::READ()->label, $this->pluralModelName, false)) {
            return true;
        }

        // Check if form is a member registration form, defined in the settings. Since it belongs to VU SA, needs a bypass.
        if (app(FormSettings::class)->member_registration_form_id === $form->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Form $form): bool
    {
        if ($this->commonChecker($user, $form, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Form $form): bool
    {
        if ($this->commonChecker($user, $form, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }
}
