<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy for Problem model authorization
 */
class ProblemPolicy extends ModelPolicy
{
    /**
     * Baseline: problems are a shared knowledge base, so every member may browse all of them.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * All authenticated users can view problems (admin routes already require auth).
     */
    #[\Override]
    public function view(User $user, Model $problem): bool
    {
        return true;
    }
}
