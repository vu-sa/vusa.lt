<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy extends ModelPolicy
{
    /**
     * Determine whether the user can view any tasks (for summary page).
     * Requires tasks.read.padalinys permission.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return $this->authorizer->allows($user, 'tasks.read.padalinys');
    }

    /**
     * Determine whether the user can view the task.
     * Compound authorization: requires both task AND taskable permissions.
     *
     * @param  Task  $task
     */
    #[\Override]
    public function view(User $user, Model $task): bool
    {
        if ($task->users->contains($user)) {
            return true;
        }

        $tenantIds = $this->authorizer->scope($user, 'tasks.read.padalinys')->tenantIds();
        if ($tenantIds->isEmpty()) {
            return false;
        }

        $taskable = $task->loadMissing('taskable')->taskable;
        if ($taskable === null) {
            return $this->tenantsFor($task, 'tenants')->pluck('id')->intersect($tenantIds)->isNotEmpty();
        }

        $permission = Task::TENANT_TASKABLE_PERMISSIONS[$taskable::class] ?? null;
        if ($permission === null) {
            return false;
        }

        $tenantIds = $tenantIds->intersect($this->authorizer->scope($user, $permission)->tenantIds());

        return $tenantIds->isNotEmpty() && $this->tenantsFor($taskable, 'tenants')->pluck('id')->intersect($tenantIds)->isNotEmpty();
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  Task  $task
     */
    #[\Override]
    public function update(User $user, Model $task): bool
    {
        if ($task->users->contains($user)) {
            return true;
        }

        return $this->commonChecker($user, $task, CRUDEnum::UPDATE->label());
    }
}
