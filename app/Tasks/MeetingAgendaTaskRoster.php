<?php

namespace App\Tasks;

use App\Actions\ResolveTaskAssignees;
use App\Models\Meeting;
use App\Models\Task;

final class MeetingAgendaTaskRoster
{
    public static function refresh(Task $task, Meeting $meeting, bool $keepExistingWhenEmpty): void
    {
        $assignees = ResolveTaskAssignees::forMeeting($meeting);

        if ($keepExistingWhenEmpty && $assignees->isEmpty()) {
            return;
        }

        $task->users()->syncOrFail($assignees->pluck('id'));
        $task->unsetRelation('users');
    }
}
