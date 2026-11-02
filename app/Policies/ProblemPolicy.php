<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Problem model authorization
 */
class ProblemPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::PROBLEM->label());
    }

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
