<?php

namespace App\Policies;

use App\Actions\GetCoordinatedRepresentatives;
use App\Models\LearningStep;
use App\Models\User;
use App\Services\LearningAdminPresenter;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;

class LearningStepPolicy extends ModelPolicy
{
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = 'learningSteps';
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        return $this->authorizer->scope($user, 'learningSteps.create.padalinys')->granted
            || $this->authorizer->scope($user, 'learningSteps.update.padalinys')->granted;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof LearningStep && $this->manageAudiences($user, $model->audiences, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof LearningStep && ! $model->is_system && $this->manageAudiences($user, $model->audiences, 'delete');
    }

    public function viewReps(User $user): bool
    {
        return LearningAdminPresenter::canSeeReps($user);
    }

    public function viewRep(User $coordinator, User $rep): bool
    {
        return $coordinator->isSuperAdmin() || GetCoordinatedRepresentatives::isCoordinatorOf($coordinator, $rep);
    }

    public function manageAudiences(User $user, iterable $audiences, string $ability): bool
    {
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");
        if ($scope->isAllScope) {
            return true;
        }
        if (! $scope->granted) {
            return false;
        }
        foreach ($audiences as $audience) {
            $tenantId = data_get($audience, 'tenant_id');
            if ($tenantId === null || ! $scope->tenantIds()->contains($tenantId)) {
                return false;
            }
        }

        return true;
    }
}
