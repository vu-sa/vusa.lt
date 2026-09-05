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
 * Progress is measured with MeetingCompletionService according to governance scope:
 * a VU SA body needs only a decision; an external body also needs student_vote
 * and student_benefit. Auto-completes once every item qualifies.
 */
class AgendaCompletionTaskHandler extends BaseTaskHandler
{
    public function __construct(protected MeetingCompletionService $completionService) {}

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
            return $this->syncTotalItems($existingTask, $meeting);
        }

        $totalItems = $meeting->agendaItems()->count();
        $completedItems = $this->countCompletedItems($meeting);

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

        if ($completedItems > 0) {
            $metadata = $task->metadata;
            $metadata['items_completed'] = $completedItems;
            $task->metadata = $metadata;
            $task->save();

            if ($totalItems > 0 && $completedItems >= $totalItems) {
                $this->complete($task, __('tasks.agenda_completion.all_items_completed'));
            }
        }

        return $task;
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

        $parts[] = __('tasks.agenda_completion.meeting_context', [
            'institution' => $institutionName,
            'date' => $meetingDate,
        ]);

        if ($assigneeCount > 1) {
            $parts[] = __('tasks.agenda_completion.assignee_context', [
                'count' => $assigneeCount - 1,
            ]);
        }

        return implode(' ', $parts);
    }

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

        if ($totalItems > 0 && $completedItems >= $totalItems) {
            $this->complete($task, __('tasks.agenda_completion.all_items_completed'), $completedBy);

            return true;
        }

        return false;
    }

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
     * An item is complete when:
     * - Type needs no vote (informational, deferred, break), OR
     * - Type is 'voting' AND has a main vote the meeting's scope considers filled.
     */
    protected function countCompletedItems(Meeting $meeting): int
    {
        $agendaItems = $meeting->agendaItems()->with('votes')->get();
        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        return $agendaItems->filter(function ($item) use ($requiresStudentPerspective) {
            if ($item->type === null) {
                return false;
            }

            if (! $item->type->requiresVote()) {
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
     * @return array{voting: int, informational: int, deferred: int, break: int, unset: int}
     */
    public function getAgendaItemTypeCounts(Meeting $meeting): array
    {
        $agendaItems = $meeting->agendaItems()->get();

        return [
            'voting' => $agendaItems->where('type', AgendaItemType::Voting)->count(),
            'informational' => $agendaItems->where('type', AgendaItemType::Informational)->count(),
            'deferred' => $agendaItems->where('type', AgendaItemType::Deferred)->count(),
            'break' => $agendaItems->where('type', AgendaItemType::Break)->count(),
            'unset' => $agendaItems->whereNull('type')->count(),
        ];
    }

    /**
     * Check if a previously completed task should reopen due to an agenda item changing to voting.
     */
    public function shouldReopenTask(Meeting $meeting): bool
    {
        $agendaItems = $meeting->agendaItems()->with('votes')->get();
        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        foreach ($agendaItems as $item) {
            if ($item->type === AgendaItemType::Voting) {
                $mainVote = $item->votes->firstWhere('is_main', true);

                if (! $mainVote) {
                    return true;
                }

                if (! $this->completionService->voteIsComplete($mainVote, $requiresStudentPerspective)) {
                    return true;
                }
            }

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

            $this->syncTotalItems($completedTask, $meeting);
        }
    }

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
