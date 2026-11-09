<?php

namespace App\Policies;

use App\Actions\GetCoordinatedRepresentatives;
use App\Enums\LearningLayer;
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

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof LearningStep && $this->allowsLayer($user, $model->layer, $model->tenant_id, 'update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof LearningStep && ! $model->is_system && $this->allowsLayer($user, $model->layer, $model->tenant_id, 'delete');
    }

    public function manageLayer(User $user, LearningLayer $layer, ?int $tenantId, string $ability = 'update'): bool
    {
        return $this->allowsLayer($user, $layer, $tenantId, $ability);
    }

    public function viewReps(User $user): bool
    {
        return LearningAdminPresenter::canSeeReps($user);
    }

    public function viewRep(User $coordinator, User $rep): bool
    {
        return $coordinator->isSuperAdmin() || GetCoordinatedRepresentatives::isCoordinatorOf($coordinator, $rep);
    }

    private function allowsLayer(User $user, LearningLayer $layer, ?int $tenantId, string $ability): bool
    {
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return $scope->isAllScope || ($scope->granted && $layer === LearningLayer::Tenant && $tenantId !== null && $scope->tenantIds()->contains($tenantId));
    }
}
