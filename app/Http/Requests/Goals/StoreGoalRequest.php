<?php

namespace App\Http\Requests\Goals;

use App\Models\Goal;

class StoreGoalRequest extends GoalRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'goals.create.padalinys';

    /**
     * `create` is tenant-agnostic; the inherited `tenant_id` rule confines the goal to a padalinys.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Goal::class);
    }
}
