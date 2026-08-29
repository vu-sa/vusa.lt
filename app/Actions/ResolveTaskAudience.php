<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Which of a task's assignees should still hear about it.
 *
 * Assignment is resolved at the *meeting's* date ({@see ResolveTaskAssignees}), so entering a
 * historical meeting files the task on whoever sat on the body back then. That is right for the
 * record and wrong for the inbox, in both directions: someone who has since left should not be
 * chased about it, and today's members should not be chased about a sitting that predates them.
 *
 * So a person is notified only when they belong to the body *both* on the task's own date and
 * today — a term that has run unbroken from the meeting to now. Belonging means holding a duty
 * there or being nominated to look after it for that term.
 *
 * Two things are deliberately left out: a task with no body behind it (a reservation, a
 * self-assigned manual task) has no term to check, and a manual task's assignees were picked by
 * a person rather than snapshotted from a roster, so neither is narrowed.
 */
class ResolveTaskAudience
{
    /**
     * @return Collection<int, User>
     */
    public static function execute(Task $task): Collection
    {
        $task->loadMissing('users');

        /** @var Collection<int, User> $assignees */
        $assignees = collect($task->users->all());

        if ($assignees->isEmpty() || self::isManual($task)) {
            return $assignees;
        }

        $institutions = self::institutionsFor($task);

        if ($institutions->isEmpty()) {
            return $assignees;
        }

        $today = Carbon::today();
        $taskDate = self::taskDateFor($task) ?? $today;

        $eligibleIds = self::belongingIds($institutions, $today);

        if (! $taskDate->isSameDay($today)) {
            $eligibleIds = $eligibleIds->intersect(self::belongingIds($institutions, $taskDate));
        }

        return $assignees
            ->filter(fn (User $user) => $eligibleIds->contains($user->id))
            ->values();
    }

    /**
     * Everyone serving in, or nominated to look after, any of the institutions on a date.
     *
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, string>
     */
    private static function belongingIds(Collection $institutions, Carbon $date): Collection
    {
        return $institutions
            ->flatMap(fn (Institution $institution) => GetInstitutionMembers::execute($institution, $date)
                ->merge(GetInstitutionAdministrators::execute($institution, $date)))
            ->pluck('id')
            ->unique()
            ->values();
    }

    /**
     * The date the task speaks about. Only a meeting has one of its own; anything else is
     * a standing obligation, judged as of today.
     */
    private static function taskDateFor(Task $task): ?Carbon
    {
        $taskable = $task->taskable;

        return $taskable instanceof Meeting ? Carbon::instance($taskable->start_time)->startOfDay() : null;
    }

    /**
     * @return Collection<int, Institution>
     */
    private static function institutionsFor(Task $task): Collection
    {
        $taskable = $task->taskable;

        if ($taskable instanceof Meeting) {
            $taskable->loadMissing('institutions');

            return collect($taskable->institutions->all());
        }

        if ($taskable instanceof Institution) {
            return collect([$taskable]);
        }

        return collect();
    }

    /**
     * Manual tasks carry a person's own choice of assignee, not a roster snapshot.
     */
    private static function isManual(Task $task): bool
    {
        return $task->action_type === null || $task->action_type === ActionType::Manual;
    }
}
