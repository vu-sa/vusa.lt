<?php

namespace App\Tasks\Handlers;

use App\Models\Meeting;
use App\Models\Task;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Collection;

/**
 * Handles Agenda Completion tasks with progress tracking for meeting agenda items.
 *
 * Agenda completion tasks are created when agenda items exist but need their
 * details filled (student_vote, decision, student_benefit). They auto-complete
 * when all agenda items have all required fields filled.
 */
class AgendaCompletionTaskHandler extends BaseTaskHandler
{
    /**
     * Find or create an agenda completion task with progress tracking.
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
            return $this->syncTotalItems($existingTask, $meeting);
        }

        $totalItems = $meeting->agendaItems()->count();
        $completedItems = $this->countCompletedItems($meeting);

        $data = CreateTaskData::withProgress(
            name: $name,
            taskable: $meeting,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::AgendaCompletion,
            totalItems: $totalItems,
        );

        $task = $this->create($data);

        // If some items are already complete, update the progress
        if ($completedItems > 0) {
            $metadata = $task->metadata;
            $metadata['items_completed'] = $completedItems;
            $task->metadata = $metadata;
            $task->save();

            // Auto-complete if all items are already done
            if ($totalItems > 0 && $completedItems >= $totalItems) {
                $this->complete($task, __('Visi darbotvarkės klausimai užpildyti'));
            }
        }

        return $task;
    }

    /**
     * Update the task when an agenda item changes.
     * Recalculates progress and auto-completes if all items are done.
     *
     * @return bool True if task was completed
     */
    public function updateProgressForMeeting(Meeting $meeting): bool
    {
        $task = $this->findExistingTask($meeting);

        if (! $task) {
            return false;
        }

        $totalItems = $meeting->agendaItems()->count();
        $completedItems = $this->countCompletedItems($meeting);

        $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
        $metadata['items_total'] = $totalItems;
        $metadata['items_completed'] = $completedItems;
        $task->metadata = $metadata;
        $task->save();

        // Auto-complete if all items are done
        if ($totalItems > 0 && $completedItems >= $totalItems) {
            $this->complete($task, __('Visi darbotvarkės klausimai užpildyti'));

            return true;
        }

        return false;
    }

    /**
     * Find an existing incomplete agenda completion task for the meeting.
     */
    public function findExistingTask(Meeting $meeting): ?Task
    {
        return Task::query()
            ->with('users')
            ->where('taskable_type', Meeting::class)
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCompletion)
            ->whereNull('completed_at')
            ->first();
    }

    /**
     * Count how many agenda items are complete (have all required fields).
     */
    protected function countCompletedItems(Meeting $meeting): int
    {
        return $meeting->agendaItems()
            ->whereNotNull('student_vote')
            ->where('student_vote', '!=', '')
            ->whereNotNull('decision')
            ->where('decision', '!=', '')
            ->whereNotNull('student_benefit')
            ->where('student_benefit', '!=', '')
            ->count();
    }

    /**
     * Sync total items count with current agenda items.
     */
    protected function syncTotalItems(Task $task, Meeting $meeting): Task
    {
        $totalItems = $meeting->agendaItems()->count();
        $completedItems = $this->countCompletedItems($meeting);

        $metadata = $task->metadata ?? ['items_total' => 0, 'items_completed' => 0];
        $metadata['items_total'] = $totalItems;
        $metadata['items_completed'] = $completedItems;
        $task->metadata = $metadata;
        $task->save();

        return $task;
    }
}
