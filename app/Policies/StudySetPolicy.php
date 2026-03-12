<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudySetPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::STUDY_SET()->label);
    }

    /**
     * Determine whether the user can view the model.
     * Study sets belong to a single tenant, so we use hasManyTenants=false
     */
    public function view(User $user, Model $studySet): bool
    {
        return $this->commonChecker($user, $studySet, CRUDEnum::READ()->label, null, false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Model $studySet): bool
    {
        return $this->commonChecker($user, $studySet, CRUDEnum::UPDATE()->label, null, false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $studySet): bool
    {
        return $this->commonChecker($user, $studySet, CRUDEnum::DELETE()->label, null, false);
    }
}
