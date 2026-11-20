<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Models\Meeting;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class TaskPolicy extends ModelPolicy
{
    /**
     * Mapping of taskable types to their permission resource names
     */
    protected array $taskablePermissionMap = [
        Meeting::class => 'meetings',
        Reservation::class => 'reservations',
    ];

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
        // If user is assigned to the task, they can always view it
        if ($task->users->contains($user)) {
            return true;
        }

        // Check if user has tasks.read.padalinys permission
        if (! $this->authorizer->allows($user, 'tasks.read.padalinys')) {
            return false;
        }

        // Get the taskable model
        /** @var Model|null $taskable */
        $taskable = $task->taskable()->first();
        if (! $taskable) {
            return false;
        }

        // Check compound authorization: user must also have permission on the taskable
        return $this->checkTaskablePermission($user, $taskable);
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

    /**
     * Check if user has permission to view the taskable model.
     * This implements compound authorization - user needs both task AND taskable permissions.
     */
    protected function checkTaskablePermission(User $user, Model $taskable): bool
    {
        $taskableClass = $taskable::class;
        $resourceName = $this->taskablePermissionMap[$taskableClass] ?? null;

        if (! $resourceName) {
            // Unknown taskable type - deny access
            return false;
        }

        $scope = $this->authorizer->scope($user, $resourceName.'.read.padalinys');

        if (! $scope->granted) {
            return false;
        }

        if ($scope->isAllScope) {
            return true;
        }

        $taskableTenants = $taskable->load('tenants')->getRelation('tenants');

        $taskableCollection = new Collection;
        if ($taskableTenants instanceof Model) {
            $taskableCollection->push($taskableTenants);
        } elseif ($taskableTenants instanceof Collection) {
            $taskableCollection = $taskableTenants;
        }

        return $taskableCollection->pluck('id')->intersect($scope->tenantIds())->isNotEmpty();
    }
}
