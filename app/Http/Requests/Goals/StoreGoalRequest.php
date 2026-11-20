<?php

namespace App\Http\Requests\Goals;

class StoreGoalRequest extends GoalRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'goals.create.padalinys';
}
