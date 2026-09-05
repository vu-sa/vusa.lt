<?php

namespace App\Tasks\Handlers;

use App\Models\Institution;
use App\Models\Task;
use App\Models\User;
use App\Support\MorphMap;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use App\ValueObjects\InstitutionActivityStatusData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Handles Periodicity Gap tasks for institutions approaching their
 * meeting periodicity threshold without a scheduled meeting.
 */
class PeriodicityGapTaskHandler extends BaseTaskHandler
{
    /**
     * @param  Collection<int, User>  $users
     */
    public function findOrCreate(
        Institution $institution,
        Collection $users,
        Carbon $dueDate,
        ?InstitutionActivityStatusData $activityStatus = null,
    ): Task {
        $existingTask = $this->findExistingTask($institution);

        if ($existingTask) {
            // Update assignees if representatives changed
            $this->syncUsers($existingTask, $users);

            return $existingTask;
        }

        $data = new CreateTaskData(
            name: __('tasks.periodicity_gap.name', ['institution' => $institution->name]),
            taskable: $institution,
            users: $users,
            dueDate: $dueDate->toDateString(),
            actionType: ActionType::PeriodicityGap,
            metadata: [
                'periodicity_days' => $institution->meeting_periodicity_days,
                'activity_status' => $activityStatus?->status->value,
                'effective_days_since_activity' => $activityStatus?->effectiveDaysSinceActivity,
            ],
            description: __('tasks.periodicity_gap.description'),
        );

        return $this->create($data);
    }

    public function completeForInstitution(Institution $institution, ?string $reason = null): bool
    {
        $task = $this->findExistingTask($institution);

        if (! $task) {
            return false;
        }

        $this->complete($task, $reason);

        return true;
    }

    public function findExistingTask(Institution $institution): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', MorphMap::alias(Institution::class))
            ->where('taskable_id', $institution->getKey())
            ->where('action_type', ActionType::PeriodicityGap)
            ->whereNull('completed_at')
            ->first();
    }

    public function hasExistingTask(Institution $institution): bool
    {
        return $this->findExistingTask($institution) !== null;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    protected function syncUsers(Task $task, Collection $users): void
    {
        $task->users()->sync($users->pluck('id'));
    }
}
