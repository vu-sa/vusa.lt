<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Form;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Settings\FormSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FormPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::FORM()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $form): bool
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
}
