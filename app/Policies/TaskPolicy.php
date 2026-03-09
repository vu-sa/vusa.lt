<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Models\Meeting;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TaskPolicy extends ModelPolicy
{
    /**
     * Mapping of taskable types to their permission resource names
     */
    protected array $taskablePermissionMap = [
        Meeting::class => 'meetings',
        Reservation::class => 'reservations',
    ];

    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::TASK()->label);
    }

    /**
     * Determine whether the user can view any tasks (for summary page).
     * Requires tasks.read.padalinys permission.
     */
    public function viewAny(User $user): bool
    {
        return $this->authorizer->forUser($user)->check('tasks.read.padalinys');
    }

    /**
     * Determine whether the user can view the task.
     * Compound authorization: requires both task AND taskable permissions.
     *
     * @param  Task  $task
     */
    public function view(User $user, Model $task): bool
    {
        // If user is assigned to the task, they can always view it
        if ($task->users->contains($user)) {
            return true;
        }

        // Check if user has tasks.read.padalinys permission
        if (! $this->authorizer->forUser($user)->check('tasks.read.padalinys')) {
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
    public function update(User $user, Model $task): bool
    {
        if ($task->users->contains($user)) {
            return true;
        }

        return $this->commonChecker($user, $task, CRUDEnum::UPDATE()->label);
    }

    /**
     * Check if user has permission to view the taskable model.
     * This implements compound authorization - user needs both task AND taskable permissions.
     */
    protected function checkTaskablePermission(User $user, Model $taskable): bool
    {
        $taskableClass = get_class($taskable);
        $resourceName = $this->taskablePermissionMap[$taskableClass] ?? null;

        if (! $resourceName) {
            // Unknown taskable type - deny access
            return false;
        }

        $permission = $resourceName.'.read.padalinys';

        // Check if user has the padalinys permission for the taskable
        if (! $this->authorizer->forUser($user)->check($permission)) {
            return false;
        }

        // Get user's permissible tenants for this permission
        $permissibleTenants = $user->tenants()
            ->whereIn('duties.id', $this->authorizer->getPermissableDuties()->pluck('id'))
            ->get();

        // Get taskable's tenants
        $taskableTenants = $taskable->load('tenants')->getRelation('tenants');

        // Convert to collection for consistent handling
        $taskableCollection = new Collection;
        if ($taskableTenants instanceof Model) {
            $taskableCollection->push($taskableTenants);
        } elseif ($taskableTenants instanceof Collection) {
            $taskableCollection = $taskableTenants;
        }

        // Check if any tenant matches
        return $taskableCollection->intersect($permissibleTenants)->isNotEmpty();
    }
}
