<?php

namespace App\Actions;

use App\Models\Cadence;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\BaseTaskHandler;
use Illuminate\Support\Carbon;

/**
 * Recompute who carries the still-open tasks of one institution term.
 *
 * Run after the administrator roster for a term changes. Because
 * {@see ResolveTaskAssignees} falls back to date-scoped members on its own, this
 * handles both directions: nominating administrators takes the task off the wider
 * membership, and removing the last one hands it back.
 *
 * Deliberately silent — assignees are synced through the relation rather than
 * {@see BaseTaskHandler::create()}, so no TaskCreated event and
 * no TaskAssignedNotification. Re-staffing a term should not send a burst of mail;
 * cutting mail down is the point of the feature.
 */
class ResyncTaskAssigneesForCadence
{
    /**
     * @return int the number of tasks whose assignees were rewritten
     */
    public static function execute(Institution $institution, Cadence $cadence): int
    {
        return self::resyncMeetingTasks($institution, $cadence)
            + self::resyncPeriodicityGapTask($institution, $cadence);
    }

    private static function resyncMeetingTasks(Institution $institution, Cadence $cadence): int
    {
        $meetings = $institution->meetings()
            ->whereBetween('start_time', [
                $cadence->start_date->startOfDay(),
                $cadence->end_date->endOfDay(),
            ])
            ->with('institutions')
            ->get();

        if ($meetings->isEmpty()) {
            return 0;
        }

        $tasks = Task::query()
            ->where('taskable_type', MorphMap::alias(Meeting::class))
            ->whereIn('taskable_id', $meetings->pluck('id'))
            ->whereIn('action_type', [ActionType::AgendaCreation, ActionType::AgendaCompletion])
            ->whereNull('completed_at')
            ->get();

        $count = 0;

        foreach ($tasks as $task) {
            $meeting = $meetings->firstWhere('id', $task->taskable_id);

            if ($meeting === null) {
                continue;
            }

            $task->users()->sync(ResolveTaskAssignees::forMeeting($meeting)->pluck('id'));
            $count++;
        }

        return $count;
    }

    /**
     * A periodicity-gap task is about the institution right now, so it is only in
     * scope when the term being edited is the one we are currently in.
     */
    private static function resyncPeriodicityGapTask(Institution $institution, Cadence $cadence): int
    {
        if (! $cadence->contains(Carbon::today())) {
            return 0;
        }

        $task = Task::query()
            ->where('taskable_type', MorphMap::alias(Institution::class))
            ->where('taskable_id', $institution->getKey())
            ->where('action_type', ActionType::PeriodicityGap)
            ->whereNull('completed_at')
            ->first();

        if ($task === null) {
            return 0;
        }

        $task->users()->sync(ResolveTaskAssignees::forInstitution($institution)->pluck('id'));

        return 1;
    }
}
