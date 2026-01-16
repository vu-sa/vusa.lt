<?php

namespace App\Tasks\Handlers;

use App\Models\Meeting;
use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Collection;

/**
 * Handles Agenda Creation tasks for meetings.
 *
 * Agenda creation tasks are created when a meeting is created without agenda items.
 * They auto-complete when the first agenda item is added to the meeting.
 */
class AgendaCreationTaskHandler extends BaseTaskHandler
{
    /**
     * Find or create an agenda creation task.
     *
     * @param  string  $name  The task name
     * @param  Meeting  $meeting  The meeting model
     * @param  Collection<int, \App\Models\User>  $users  Users assigned to the task
     * @param  string|null  $dueDate  Due date for the task
     */
    public function findOrCreate(
        string $name,
        Meeting $meeting,
        Collection $users,
        ?string $dueDate = null
    ): Task {
        $existingTask = $this->findExistingTask($meeting);

        if ($existingTask) {
            return $existingTask;
        }

        $data = new CreateTaskData(
            name: $name,
            taskable: $meeting,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::AgendaCreation,
        );

        return $this->create($data);
    }

    /**
     * Complete the agenda creation task for a meeting.
     * Called when the first agenda item is created.
     */
    public function completeForMeeting(Meeting $meeting): bool
    {
        $task = $this->findExistingTask($meeting);

        if (! $task) {
            return false;
        }

        $this->complete($task, __('Pirmas darbotvarkės klausimas sukurtas'));

        return true;
    }

    /**
     * Find an existing incomplete agenda creation task for the meeting.
     */
    public function findExistingTask(Meeting $meeting): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', Meeting::class)
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCreation)
            ->whereNull('completed_at')
            ->first();
    }
}
