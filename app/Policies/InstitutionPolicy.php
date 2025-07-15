<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InstitutionPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::INSTITUTION()->label);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Model $institution): bool
    {
        return $this->commonChecker($user, $institution, CRUDEnum::READ()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $institution): bool
    {
        // For institutions, "own" scope only applies to read operations
        // Since institutions.update.own permission doesn't exist, commonChecker will only
        // check padalinys and * scopes automatically
        return $this->commonChecker($user, $institution, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $institution): bool
    {
        // For institutions, "own" scope only applies to read operations
        // Since institutions.delete.own permission doesn't exist, commonChecker will only
        // check padalinys and * scopes automatically
        return $this->commonChecker($user, $institution, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
