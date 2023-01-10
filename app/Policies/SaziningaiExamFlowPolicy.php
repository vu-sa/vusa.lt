<?php

namespace App\Policies;

use App\Models\SaziningaiExamFlow;
use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaziningaiExamFlowPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::SAZININGAI_EXAM_FLOW()->label);
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return $this->forUser($user)->check($this->pluralModelName . '.index.padalinys');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return $user->can('create saziningai content');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SaziningaiExamFlow $saziningaiExamFlow)
    {
        return $user->can('edit saziningai content');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SaziningaiExamFlow $saziningaiExamFlow)
    {
        return $user->can('delete saziningai content');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamFlow  $saziningaiExamFlow
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SaziningaiExamFlow $saziningaiExamFlow)
    {
        //
    }
}
