<?php

namespace App\Tasks\Handlers;

use App\Models\Institution;
use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Handles Periodicity Gap tasks for institutions.
 *
 * Periodicity gap tasks are created when an institution is approaching its
 * meeting periodicity threshold with no scheduled meeting. They prompt
 * representatives to either schedule a new meeting or create a check-in record.
 *
 * They can be manually completed by users, or auto-complete when a meeting
 * or check-in is created for the institution.
 */
class PeriodicityGapTaskHandler extends BaseTaskHandler
{
    /**
     * Find or create a periodicity gap task for an institution.
     *
     * @param  Institution  $institution  The institution model
     * @param  Collection<int, \App\Models\User>  $users  Users assigned to the task
     * @param  Carbon  $dueDate  Due date for the task (threshold date)
     */
    public function findOrCreate(
        Institution $institution,
        Collection $users,
        Carbon $dueDate
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
            ],
            description: __('tasks.periodicity_gap.description'),
        );

        return $this->create($data);
    }

    /**
     * Complete the periodicity gap task for an institution.
     * Called when a meeting or check-in is created.
     *
     * @param  Institution  $institution  The institution
     * @param  string|null  $reason  Completion reason message
     */
    public function completeForInstitution(Institution $institution, ?string $reason = null): bool
    {
        $task = $this->findExistingTask($institution);

        if (! $task) {
            return false;
        }

        $this->complete($task, $reason);

        return true;
    }

    /**
     * Find an existing incomplete periodicity gap task for the institution.
     */
    public function findExistingTask(Institution $institution): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', Institution::class)
            ->where('taskable_id', $institution->getKey())
            ->where('action_type', ActionType::PeriodicityGap)
            ->whereNull('completed_at')
            ->first();
    }

    /**
     * Check if an institution has an existing periodicity gap task.
     */
    public function hasExistingTask(Institution $institution): bool
    {
        return $this->findExistingTask($institution) !== null;
    }

    /**
     * Sync task users with new collection of representatives.
     *
     * @param  Task  $task  The task to update
     * @param  Collection<int, \App\Models\User>  $users  New set of users
     */
    protected function syncUsers(Task $task, Collection $users): void
    {
        $task->users()->sync($users->pluck('id'));
    }
}
