<?php

namespace App\Actions\Schedulable;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Support\Facades\Notification;

class TaskNotifier
{
    public static function notifyDaysLeft(int $daysLeft)
    {
        $tasks = Task::with('users')->whereDate('due_date', '=', now()->addDays($daysLeft))->where('completed_at', null)->get();

        foreach ($tasks as $task) {
            Notification::send($task->users, new TaskReminderNotification($task, $daysLeft));
        }
    }
}
