<?php

namespace App\Tasks\Synchronizers;

use App\Enums\ApprovalDecision;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\ResponsibilityResolver;
use App\Tasks\DTOs\CreateTaskData;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\ApprovalTaskHandler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ReservationReviewTaskSynchronizer
{
    public function __construct(private readonly ApprovalTaskHandler $handler) {}

    public function requested(ReservationResource $item, int $step): Task
    {
        return DB::transaction(function () use ($item, $step): Task {
            Reservation::query()->whereKey($item->reservation_id)->lockForUpdate()->firstOrFail();

            return $this->assign($item, $step, false);
        });
    }

    public function decided(ReservationResource $item, int $step, ApprovalDecision $decision, ?User $actor): void
    {
        if (! $decision->isTerminating() && ! $item->isStepComplete($step)) {
            return;
        }

        $tasks = $this->openTasks($item->reservation_id)
            ->where('metadata->reservation_resource_id', $item->id)
            ->when(! $decision->isTerminating(), fn (Builder $query) => $query->where('metadata->approval_step', $step))
            ->get()
            ->concat(Task::query()->whereMorphedTo('taskable', $item)
                ->where('action_type', ActionType::Approval)->whereNull('completed_at')->get());

        $reviewers = $item->getApproversForStep($step);
        foreach ($tasks as $task) {
            $task->users()->sync($reviewers->pluck('id'));
            $task->unsetRelation('users');
            $this->handler->complete($task, __('Approval decision was made'), $actor);
        }
    }

    public function reconcileAll(bool $notify = true): void
    {
        app(ResponsibilityResolver::class)->flush();

        Reservation::query()
            ->where(fn (Builder $query) => $query
                ->whereHas('resources', fn (Builder $resources) => $resources->where('reservation_resource.state', 'created'))
                ->orWhereHas('tasks', fn (Builder $tasks) => $tasks->where('action_type', ActionType::Approval)->whereNull('completed_at')))
            ->eachById(fn (Reservation $reservation) => $this->reconcile($reservation, $notify), 100);
    }

    public function reconcile(Reservation $reservation, bool $notify = true): void
    {
        DB::transaction(function () use ($reservation, $notify): void {
            Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $items = ReservationResource::query()->where('reservation_id', $reservation->id)
                ->with(['resource.tenant', 'reservation'])->get();
            $this->linkLegacyItemTasks($reservation, $items);
            $tasks = $this->openTasks($reservation->id)->get();
            $legacy = $tasks->filter(fn (Task $task): bool => ! isset($task->metadata['reservation_resource_id']));
            $legacyDeadline = $legacy->min('due_date');

            foreach ($items as $item) {
                $this->reconcileItem($item, $notify && $legacy->isEmpty(), $legacyDeadline);
            }

            $this->closeObsoleteTasks($items, $tasks, $legacy);
        });
    }

    /** @param Collection<int, ReservationResource> $items */
    private function linkLegacyItemTasks(Reservation $reservation, Collection $items): void
    {
        Task::query()->where('taskable_type', (new ReservationResource)->getMorphClass())
            ->whereIn('taskable_id', $items->modelKeys())->where('action_type', ActionType::Approval)
            ->whereNull('completed_at')->each(function (Task $task) use ($items, $reservation): void {
                $item = $items->firstWhere('id', $task->taskable_id);
                $task->update([
                    'taskable_type' => $reservation->getMorphClass(), 'taskable_id' => $reservation->id,
                    'metadata' => [...($task->metadata ?? []), 'reservation_resource_id' => $item->id, 'approval_step' => $item->currentApprovalStep()],
                ]);
            });
    }

    /**
     * @param  Collection<int, ReservationResource>  $items
     * @param  Collection<int, Task>  $tasks
     * @param  Collection<int, Task>  $legacy
     */
    private function closeObsoleteTasks(Collection $items, Collection $tasks, Collection $legacy): void
    {
        foreach ($tasks as $task) {
            if ($legacy->contains('id', $task->id)) {
                $task->delete();

                continue;
            }

            $item = $items->firstWhere('id', $task->metadata['reservation_resource_id']);
            if ($item === null || $item->resource === null || $item->state->getValue() !== 'created') {
                $task->update(['completed_at' => now()]);
            }
        }
    }

    private function reconcileItem(ReservationResource $item, bool $notify, ?Carbon $legacyDeadline): void
    {
        if ($item->resource === null || $item->state->getValue() !== 'created') {
            return;
        }

        $flow = $item->getApprovalFlow();
        $steps = $flow !== null && ! $flow->is_sequential
            ? range(1, $flow->total_steps)
            : [$item->currentApprovalStep()];

        foreach ($steps as $step) {
            if ($item->isStepComplete($step)) {
                continue;
            }

            $task = $this->assign($item, $step, $notify);
            if ($legacyDeadline !== null && ($task->due_date === null || $task->due_date->gt($legacyDeadline))) {
                $task->update(['due_date' => $legacyDeadline]);
            }
        }
    }

    private function assign(ReservationResource $item, int $step, bool $notify): Task
    {
        $task = $this->openTasks($item->reservation_id)
            ->where('metadata->reservation_resource_id', $item->id)
            ->where('metadata->approval_step', $step)->first();
        $users = $item->getApproversForStep($step);
        $previous = $task?->users()->pluck('users.id') ?? collect();

        if ($task === null) {
            $days = $item->getApprovalFlow()?->getDeadlineDaysForStep($step);
            $task = $this->handler->create(new CreateTaskData(
                name: __('Patvirtinti arba atmesti').': '.$item->getApprovalDisplayName(),
                taskable: $item->reservation,
                users: $users,
                dueDate: $days ? now()->addDays($days)->toDateTimeString() : null,
                actionType: ActionType::Approval,
                metadata: ['reservation_resource_id' => $item->id, 'approval_step' => $step],
            ));
        } else {
            $task->users()->sync($users->pluck('id'));
        }

        if ($users->isEmpty() && ($task->wasRecentlyCreated || $previous->isNotEmpty())) {
            Log::warning('No current reservation handlers', [
                'reservation_id' => $item->reservation_id,
                'reservation_resource_id' => $item->id,
                'tenant_id' => $item->resource?->tenant_id,
            ]);
        }

        if ($notify) {
            $added = $users->reject(fn (User $user): bool => $previous->contains($user->id) || $user->is(auth()->user()));
            DB::afterCommit(fn () => Notification::send($added, new ApprovalRequestedNotification($item, $step)));
        }

        return $task;
    }

    /** @return Builder<Task> */
    private function openTasks(string $reservationId): Builder
    {
        return Task::query()->where('taskable_type', (new Reservation)->getMorphClass())
            ->where('taskable_id', $reservationId)->where('action_type', ActionType::Approval)->whereNull('completed_at');
    }
}
