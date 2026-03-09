<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
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
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::PROBLEM()->label);
    }

    /**
     * Determine whether the user can view the model.
     *
     * All authenticated users can view problems (admin routes already require auth).
     */
    public function view(User $user, Model $problem): bool
    {
        return true;
    }

    /**
     * Override update method to ensure proper type hinting
     */
    public function update(User $user, Model $problem): bool
    {
        return $this->commonChecker($user, $problem, CRUDEnum::UPDATE()->label, $this->pluralModelName, false);
    }

    /**
     * Override delete method to ensure proper type hinting
     */
    public function delete(User $user, Model $problem): bool
    {
        return $this->commonChecker($user, $problem, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }

    /**
     * Override restore method to ensure proper type hinting and single tenant handling
     */
    public function restore(User $user, Model $problem): bool
    {
        return $this->commonChecker($user, $problem, CRUDEnum::DELETE()->label, $this->pluralModelName, false);
    }
}
