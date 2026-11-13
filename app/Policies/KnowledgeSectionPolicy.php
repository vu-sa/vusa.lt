<?php

namespace App\Policies;

use App\Models\KnowledgeSection;
use App\Models\User;
use App\Services\ModelAuthorizer;

class KnowledgeSectionPolicy
{
    public function __construct(private readonly ModelAuthorizer $authorizer) {}

    public function create(User $user): bool
    {
        return $this->authorizer->scope($user, 'knowledgeItems.create.padalinys')->granted
            || $this->authorizer->scope($user, 'learningSteps.create.padalinys')->granted;
    }

    public function update(User $user, KnowledgeSection $section): bool
    {
        return $this->target($user, $section->tenant_id, 'update');
    }

    public function delete(User $user, KnowledgeSection $section): bool
    {
        return $this->target($user, $section->tenant_id, 'delete');
    }

    public function target(User $user, ?int $tenantId, string $ability): bool
    {
        foreach (['knowledgeItems', 'learningSteps'] as $resource) {
            $scope = $this->authorizer->scope($user, "{$resource}.{$ability}.padalinys");
            if ($scope->isAllScope || ($scope->granted && $tenantId !== null && $scope->tenantIds()->contains($tenantId))) {
                return true;
            }
        }

        return false;
    }
}
