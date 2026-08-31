<?php

namespace App\Tasks\Handlers;

use App\Actions\ResolveTaskAssignees;
use App\Actions\ResyncTaskAssigneesForCadence;
use App\Enums\AgendaItemType;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Services\MeetingCompletionService;
use App\Support\MorphMap;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Collection;

/**
 * Handles Agenda Completion tasks with progress tracking for meeting agenda items.
 *
 * Agenda completion tasks are created when agenda items exist but need their vote details
 * filled. Which details count is the meeting's scope question, so progress is measured with
 * MeetingCompletionService: a VU SA body's item needs only a decision, an external body's
 * also needs student_vote and student_benefit. They auto-complete once every item qualifies.
 */
class AgendaCompletionTaskHandler extends BaseTaskHandler
{
    public function __construct(protected MeetingCompletionService $completionService) {}

    /**
     * Find or create an agenda completion task with progress tracking.
     *
     * @param  string  $name  The task name
     * @param  Meeting  $meeting  The meeting model
     * @param  \Illuminate\Database\Eloquent\Collection<int, User>|Collection<int, User>  $users  Users assigned to the task
     * @param  string|null  $dueDate  Due date for the task
     */
    public function findOrCreate(
        string $name,
        Meeting $meeting,
        \Illuminate\Database\Eloquent\Collection|Collection $users,
        ?string $dueDate = null
    ): Task {
        $existingTask = $this->findExistingTask($meeting);

        if ($existingTask) {
            return $this->syncTotalItems($existingTask, $meeting);
        }

        $totalItems = $meeting->agendaItems()->count();
        $completedItems = $this->countCompletedItems($meeting);

        // Generate contextual description with assignee count and meeting context
        $description = $this->generateDescription($meeting, $users);

        $data = CreateTaskData::withProgress(
            name: $name,
            taskable: $meeting,
            users: $users,
            dueDate: $dueDate,
            actionType: ActionType::AgendaCompletion,
            totalItems: $totalItems,
            description: $description,
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
                $this->complete($task, __('tasks.agenda_completion.all_items_completed'));
            }
        }

        return $task;
    }

    /**
     * Generate a contextual description for the task.
     *
     * @param  Meeting  $meeting  The meeting model
     * @param  Collection<int, User>  $users  Users assigned to the task
     */
    protected function generateDescription(Meeting $meeting, Collection $users): string
    {
        $meeting->loadMissing('institutions');

        $institutionName = $meeting->institutions->first()->name ?? __('Nežinoma institucija');
        $meetingDate = $meeting->start_time->format('Y-m-d');
        $assigneeCount = $users->count();

        $parts = [];

        // Add meeting context
        $parts[] = __('tasks.agenda_completion.meeting_context', [
            'institution' => $institutionName,
            'date' => $meetingDate,
        ]);

        // Add assignee context if there are multiple assignees
        if ($assigneeCount > 1) {
            $parts[] = __('tasks.agenda_completion.assignee_context', [
                'count' => $assigneeCount - 1,
            ]);
        }

        return implode(' ', $parts);
    }

    /**
     * Update the task when an agenda item changes.
     * Recalculates progress and auto-completes if all items are done.
     *
     * @param  User|null  $completedBy  The user who triggered the update (excluded from notifications)
     * @return bool True if task was completed
     */
    public function updateProgressForMeeting(Meeting $meeting, ?User $completedBy = null): bool
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
            $this->complete($task, __('tasks.agenda_completion.all_items_completed'), $completedBy);

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
            ->where('taskable_type', MorphMap::alias(Meeting::class))
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCompletion)
            ->whereNull('completed_at')
            ->first();
    }

    /**
     * Count how many agenda items are complete.
     *
     * An item is complete when:
     * - Type is 'informational' or 'deferred' (no vote required), OR
     * - Type is 'voting' AND has a main vote the meeting's scope considers filled — a decision
     *   alone for a VU SA body, decision plus student_vote and student_benefit for an external one.
     *
     * Items with null type are NOT counted as complete (user must select type first).
     */
    protected function countCompletedItems(Meeting $meeting): int
    {
        $agendaItems = $meeting->agendaItems()->with('votes')->get();
        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        return $agendaItems->filter(function ($item) use ($requiresStudentPerspective) {
            // Items without type selected are incomplete
            if ($item->type === null) {
                return false;
            }

            // Informational and deferred items are complete without votes
            if ($item->type === AgendaItemType::Informational || $item->type === AgendaItemType::Deferred) {
                return true;
            }

            $mainVote = $item->votes->firstWhere('is_main', true);

            if (! $mainVote) {
                return false;
            }

            return $this->completionService->voteIsComplete($mainVote, $requiresStudentPerspective);
        })->count();
    }

    /**
     * Get agenda item counts by type for use in notifications.
     *
     * @return array{voting: int, informational: int, deferred: int, unset: int}
     */
    public function getAgendaItemTypeCounts(Meeting $meeting): array
    {
        $agendaItems = $meeting->agendaItems()->get();

        return [
            'voting' => $agendaItems->where('type', AgendaItemType::Voting)->count(),
            'informational' => $agendaItems->where('type', AgendaItemType::Informational)->count(),
            'deferred' => $agendaItems->where('type', AgendaItemType::Deferred)->count(),
            'unset' => $agendaItems->whereNull('type')->count(),
        ];
    }

    /**
     * Check if a previously complete task should be reopened due to type change.
     *
     * This is called when an agenda item's type changes from informational/deferred
     * to voting, and the voting item doesn't have a complete main vote.
     */
    public function shouldReopenTask(Meeting $meeting): bool
    {
        $agendaItems = $meeting->agendaItems()->with('votes')->get();
        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        foreach ($agendaItems as $item) {
            // Check if any voting item is incomplete
            if ($item->type === AgendaItemType::Voting) {
                $mainVote = $item->votes->firstWhere('is_main', true);

                if (! $mainVote) {
                    return true;
                }

                if (! $this->completionService->voteIsComplete($mainVote, $requiresStudentPerspective)) {
                    return true;
                }
            }

            // Items without type are incomplete
            if ($item->type === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reopen a completed task if conditions require it.
     *
     * Reopening revives a roster that was snapshotted whenever the task was last open, so
     * the assignees are resolved again on the way back: a term staffed with administrators
     * after the task completed would otherwise mail its whole membership on the next
     * auto-completion. Silent, like {@see ResyncTaskAssigneesForCadence} — the
     * task is the same one, only its holders changed.
     */
    public function reopenIfNeeded(Meeting $meeting): void
    {
        // Find a completed task for this meeting
        $completedTask = Task::query()
            ->where('taskable_type', MorphMap::alias(Meeting::class))
            ->where('taskable_id', $meeting->getKey())
            ->where('action_type', ActionType::AgendaCompletion)
            ->whereNotNull('completed_at')
            ->first();

        if (! $completedTask) {
            return;
        }

        if ($this->shouldReopenTask($meeting)) {
            $completedTask->completed_at = null;
            $completedTask->save();

            $assignees = ResolveTaskAssignees::forMeeting($meeting);

            // An empty resolution means the body has nobody to hand it to; leaving the
            // stale list beats leaving the task unassigned.
            if ($assignees->isNotEmpty()) {
                $completedTask->users()->sync($assignees->pluck('id'));
            }

            // Update progress
            $this->syncTotalItems($completedTask, $meeting);
        }
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
