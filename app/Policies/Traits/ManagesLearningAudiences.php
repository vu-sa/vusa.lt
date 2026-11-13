<?php

namespace App\Policies\Traits;

use App\Models\User;

trait ManagesLearningAudiences
{
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
