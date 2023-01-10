<?php

namespace App\Policies;

use App\Models\SaziningaiExamObserver;
use App\Models\User;
use App\Policies\Traits\UseUserDutiesForAuthorization;
use Illuminate\Support\Str;
use App\Enums\ModelEnum;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaziningaiExamObserverPolicy
{
    use HandlesAuthorization, UseUserDutiesForAuthorization;

    private $pluralModelName;

    public function __construct()
    {
        $this->pluralModelName = Str::plural(ModelEnum::SAZININGAI_EXAM_OBSERVER()->label);
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
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SaziningaiExamObserver $saziningaiExamObserver)
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
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SaziningaiExamObserver $saziningaiExamObserver)
    {
        return $user->can('edit saziningai content');
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SaziningaiExamObserver $saziningaiExamObserver)
    {
        return $user->can('delete saziningai content');
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SaziningaiExamObserver $saziningaiExamObserver)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExamObserver  $saziningaiExamObserver
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SaziningaiExamObserver $saziningaiExamObserver)
    {
        //
    }
}
