<?php

namespace App\Tasks\Handlers;

use App\Enums\AgendaItemType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\User;
use App\Services\MeetingCompletionService;
use App\Support\MorphMap;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use App\Tasks\MeetingAgendaTaskRoster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    protected function countCompletedItems(Meeting $meeting): int
    {
        $requiresStudentPerspective = $meeting->requiresStudentPerspective();

        return $meeting->agendaItems()->with('votes')->get()
            ->filter(fn (AgendaItem $item): bool => $this->completionService->itemIsComplete($item, $requiresStudentPerspective))
            ->count();
    }

    /**
     * @return array{voting: int, informational: int, deferred: int, break: int, withdrawn: int, unset: int}
     */
    public function getAgendaItemTypeCounts(Meeting $meeting): array
    {
        $agendaItems = $meeting->relationLoaded('agendaItems') ? $meeting->agendaItems : $meeting->agendaItems()->get();

        return [
            'voting' => $agendaItems->where('type', AgendaItemType::Voting)->count(),
            'informational' => $agendaItems->where('type', AgendaItemType::Informational)->count(),
            'deferred' => $agendaItems->where('type', AgendaItemType::Deferred)->count(),
            'break' => $agendaItems->where('type', AgendaItemType::Break)->count(),
            'withdrawn' => $agendaItems->where('type', AgendaItemType::Withdrawn)->count(),
            'unset' => $agendaItems->whereNull('type')->count(),
        ];
    }

    /**
     * A completed task reopens once any agenda item stops counting as complete (e.g. switched to voting).
     */
    public function shouldReopenTask(Meeting $meeting): bool
    {
        return $this->countCompletedItems($meeting) < $meeting->agendaItems()->count();
    }

    public function reopenIfNeeded(Meeting $meeting): void
    {
        DB::transaction(function () use ($meeting): void {
            $completedTask = Task::query()
                ->where('taskable_type', MorphMap::alias(Meeting::class))
                ->where('taskable_id', $meeting->getKey())
                ->where('action_type', ActionType::AgendaCompletion)
                ->whereNotNull('completed_at')
                ->lockForUpdate()
                ->first();

            if (! $completedTask || ! $this->shouldReopenTask($meeting)) {
                return;
            }

            $completedTask->completed_at = null;

            // Keep a holder on the reopened task when nobody can take it over.
            MeetingAgendaTaskRoster::refresh($completedTask, $meeting, keepExistingWhenEmpty: true);
            $this->syncTotalItems($completedTask, $meeting);
        });
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
