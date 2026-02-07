<?php

namespace App\Tasks\Handlers;

use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
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
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>|\Illuminate\Support\Collection<int, \App\Models\User>  $users  Users assigned to the task
     * @param  string|null  $dueDate  Due date for the task
     */
    public function findOrCreate(
        string $name,
        Meeting $meeting,
        \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection $users,
        ?string $dueDate = null
    ): Task {
        $existingTask = $this->findExistingTask($meeting);

        if ($existingTask) {
            return $existingTask;
        }

        // Generate contextual description with assignee count and meeting context
        $description = $this->generateDescription($meeting, $users);

        $data = new CreateTaskData(
            name: $name,
            taskable: $meeting,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::AgendaCreation,
            description: $description,
        );

        return $this->create($data);
    }

    /**
     * Generate a contextual description for the task.
     *
     * @param  Meeting  $meeting  The meeting model
     * @param  Collection<int, \App\Models\User>  $users  Users assigned to the task
     */
    protected function generateDescription(Meeting $meeting, Collection $users): string
    {
        $meeting->loadMissing('institutions');

        $institutionName = $meeting->institutions->first()->name ?? __('Nežinoma institucija');
        $meetingDate = $meeting->start_time->format('Y-m-d');
        $assigneeCount = $users->count();

        $parts = [];

        // Add meeting context
        $parts[] = __('tasks.agenda_creation.meeting_context', [
            'institution' => $institutionName,
            'date' => $meetingDate,
        ]);

        // Add assignee context if there are multiple assignees
        if ($assigneeCount > 1) {
            $parts[] = __('tasks.agenda_creation.assignee_context', [
                'count' => $assigneeCount - 1,
            ]);
        }

        return implode(' ', $parts);
    }

    /**
     * Complete the agenda creation task for a meeting.
     * Called when the first agenda item is created.
     *
     * @param  User|null  $completedBy  The user who created the first agenda item
     */
    public function completeForMeeting(Meeting $meeting, ?User $completedBy = null): bool
    {
        $task = $this->findExistingTask($meeting);

        if (! $task) {
            return false;
        }

        $this->complete($task, __('tasks.agenda_creation.first_item_created'), $completedBy);

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
