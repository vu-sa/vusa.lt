<?php

namespace App\Policies;

use App\Models\LearningSection;
use App\Models\User;
use App\Services\ModelAuthorizer;

class LearningSectionPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function create(User $user): bool
    {
        return $this->authorizer->scope($user, 'learningSteps.create.padalinys')->granted;
    }

    public function update(User $user, LearningSection $section): bool
    {
        return $this->target($user, $section->tenant_id, 'update');
    }

    public function delete(User $user, LearningSection $section): bool
    {
        return $this->target($user, $section->tenant_id, 'delete');
    }

    public function target(User $user, ?int $tenantId, string $ability): bool
    {
        $scope = $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys");

        return $scope->isAllScope || ($scope->granted && $tenantId !== null && $scope->tenantIds()->contains($tenantId));
    }
}
