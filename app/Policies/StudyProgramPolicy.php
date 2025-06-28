<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StudyProgramPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::STUDY_PROGRAM()->label);
    }

    /**
     * Determine whether the user can view the model.
     * Study programs belong to a single tenant, so we use hasManyTenants=false
     */
    public function view(User $user, Model $studyProgram): bool
    {
        return $this->commonChecker($user, $studyProgram, CRUDEnum::READ()->label, null, false);
    }

    /**
     * Determine whether the user can update the model.
     * Study programs belong to a single tenant, so we use hasManyTenants=false
     */
    public function update(User $user, Model $studyProgram): bool
    {
        return $this->commonChecker($user, $studyProgram, CRUDEnum::UPDATE()->label, null, false);
    }

    /**
     * Determine whether the user can delete the model.
     * Study programs belong to a single tenant, so we use hasManyTenants=false
     */
    public function delete(User $user, Model $studyProgram): bool
    {
        return $this->commonChecker($user, $studyProgram, CRUDEnum::DELETE()->label, null, false);
    }
}
