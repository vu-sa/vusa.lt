<?php

namespace App\Policies;

use App\Models\KnowledgeItem;
use App\Models\User;
use App\Policies\Traits\ManagesLearningAudiences;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;

/**
 * Pages are edited with `knowledgeItems.*` (padalinys = the page's unit; a VU SA-wide page needs `*`).
 * Who a page is for stays with the path builders: audiences need `learningSteps.*`, and those
 * managers may also edit the pages whose audiences they manage.
 */
class KnowledgeItemPolicy extends ModelPolicy
{
    use ManagesLearningAudiences;

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = 'knowledgeItems';
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return true;
    }

    public function manage(User $user): bool
    {
        foreach (['knowledgeItems', 'learningSteps'] as $resource) {
            foreach (['create', 'update'] as $ability) {
                if ($this->authorizer->scope($user, "{$resource}.{$ability}.padalinys")->granted) {
                    return true;
                }
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $this->authorizer->scope($user, 'knowledgeItems.create.padalinys')->granted
            || $this->authorizer->scope($user, 'learningSteps.create.padalinys')->granted;
    }

    public function update(User $user, Model $model): bool
    {
        return $model instanceof KnowledgeItem && ($this->editContent($user, $model, 'update') || $this->managesPathOf($user, $model, 'update'));
    }

    public function delete(User $user, Model $model): bool
    {
        return $model instanceof KnowledgeItem && ($this->editContent($user, $model, 'delete') || $this->managesPathOf($user, $model, 'delete'));
    }

    public function editContent(User $user, KnowledgeItem $item, string $ability): bool
    {
        return $this->targetTenant($user, $item->tenant_id, $ability, 'knowledgeItems');
    }

    /** Whether the user may place a page in this unit (null = VU SA-wide) with either permission. */
    public function targetTenant(User $user, ?int $tenantId, string $ability, ?string $only = null): bool
    {
        foreach ($only !== null ? [$only] : ['knowledgeItems', 'learningSteps'] as $resource) {
            $scope = $this->authorizer->scope($user, "{$resource}.{$ability}.padalinys");
            if ($scope->isAllScope || ($scope->granted && $tenantId !== null && $scope->tenantIds()->contains($tenantId))) {
                return true;
            }
        }

        return false;
    }

    public function managesPathOf(User $user, KnowledgeItem $item, string $ability): bool
    {
        return $item->audiences->isNotEmpty() && $this->manageAudiences($user, $item->audiences, $ability);
    }

    public function canManageAudiences(User $user, string $ability): bool
    {
        return $this->authorizer->scope($user, "learningSteps.{$ability}.padalinys")->granted;
    }
}
