<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Goal;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\Experiments\GoalsExperiment;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pilot members read enabled tenants' goals; writes also require scoped permissions.
 */
class GoalPolicy extends ModelPolicy
{
    #[\Override]
    protected bool $hasManyTenants = false;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::GOAL->label());
    }

    #[\Override]
    public function viewAny(User $user): bool
    {
        return GoalsExperiment::enabledForUser($user);
    }

    #[\Override]
    public function view(User $user, Model $goal): bool
    {
        return $goal instanceof Goal && GoalsExperiment::enabledForTenant($goal->tenant) && GoalsExperiment::enabledForUser($user);
    }

    #[\Override]
    public function create(User $user): bool
    {
        return GoalsExperiment::enabledForUser($user)
            && $this->authorizer->tenants($user, 'goals.create.padalinys')->whereIn('id', GoalsExperiment::enabledTenantIds())->isNotEmpty();
    }

    #[\Override]
    public function update(User $user, Model $goal): bool
    {
        return $this->view($user, $goal) && $this->commonChecker($user, $goal, CRUDEnum::UPDATE->label(), $this->pluralModelName, false);
    }

    #[\Override]
    public function delete(User $user, Model $goal): Response|bool
    {
        return $this->view($user, $goal) && $this->commonChecker($user, $goal, CRUDEnum::DELETE->label(), $this->pluralModelName, false);
    }
}
