<?php

namespace App\Tasks\Handlers;

use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Support\MorphMap;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Collection;

class AgendaCreationTaskHandler extends BaseTaskHandler
{
    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>|Collection<int, User>  $users
     */
    public function findOrCreate(
        string $name,
        Meeting $meeting,
        \Illuminate\Database\Eloquent\Collection|Collection $users,
        ?string $dueDate = null
    ): Task {
        $existingTask = $this->findExistingTask($meeting);

        if ($existingTask) {
            return $existingTask;
        }

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
     * @param  Collection<int, User>  $users
     */
    protected function generateDescription(Meeting $meeting, Collection $users): string
    {
        $meeting->loadMissing('institutions');

        $institutionName = $meeting->institutions->first()->name ?? __('Nežinoma institucija');
        $meetingDate = $meeting->start_time->format('Y-m-d');
        $assigneeCount = $users->count();

        $parts = [];

        $parts[] = __('tasks.agenda_creation.meeting_context', [
            'institution' => $institutionName,
            'date' => $meetingDate,
        ]);

        if ($assigneeCount > 1) {
            $parts[] = __('tasks.agenda_creation.assignee_context', [
                'count' => $assigneeCount - 1,
            ]);
        }

        return implode(' ', $parts);
    }

    public function completeForMeeting(Meeting $meeting, ?User $completedBy = null): bool
    {
        $task = $this->findExistingTask($meeting);

        if (! $task) {
            return false;
        }

        $this->complete($task, __('tasks.agenda_creation.first_item_created'), $completedBy);

        return true;
    }

    public function findExistingTask(Meeting $meeting): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', MorphMap::alias(Meeting::class))
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCreation)
            ->whereNull('completed_at')
            ->first();
    }
}
