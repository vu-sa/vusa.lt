<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Training;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Str;

class TrainingPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::TAG()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Training $training): bool
    {
        if ($this->commonChecker($user, $training, CRUDEnum::READ()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Training $training): bool
    {
        if ($this->commonChecker($user, $training, CRUDEnum::UPDATE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Training $training): bool
    {
        if ($this->commonChecker($user, $training, CRUDEnum::DELETE()->label, $this->pluralModelName)) {
            return true;
        }

        return false;
    }
}
