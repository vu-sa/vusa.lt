<?php

namespace App\Policies;

use App\Models\SaziningaiExam;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SaziningaiExamPolicy
{
    use HandlesAuthorization;

    public function before(User $user, $ability)
    {
        // dd($user->isAdmin());
        
        if ($user->isAdmin()) {
            return true;
        }
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, SaziningaiExam $saziningaiExam)
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, SaziningaiExam $saziningaiExam)
    {
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, SaziningaiExam $saziningaiExam)
    {
        //
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, SaziningaiExam $saziningaiExam)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\SaziningaiExam  $saziningaiExam
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, SaziningaiExam $saziningaiExam)
    {
        //
    }
}
