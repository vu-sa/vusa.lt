<?php

namespace App\Actions\Schedulable;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Support\Facades\Notification;

class TaskNotifier
{
    /**
     * Remind each task's audience, skipping anyone who deselected this interval on Pranešimų nustatymai.
     */
    public static function notifyDaysLeft(int $daysLeft): void
    {
        $tasks = Task::with('users', 'taskable')->whereDate('due_date', '=', now()->addDays($daysLeft))->where('completed_at', null)->get();

        foreach ($tasks as $task) {
            $recipients = $task->notifiableUsers()
                ->filter(fn (User $user): bool => in_array($daysLeft, $user->getTaskReminderDays(), true));

            Notification::send($recipients, new TaskReminderNotification($task, $daysLeft));
        }
    }
}
