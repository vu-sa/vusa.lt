<?php

namespace App\Actions\Schedulable;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Tasks\Enums\ActionType;
use App\Tasks\Synchronizers\ReservationReviewTaskSynchronizer;
use Illuminate\Support\Facades\Notification;

class TaskNotifier
{
    /**
     * Remind each task's audience, skipping anyone who deselected this interval on Pranešimų nustatymai.
     * Periodicity gap tasks are skipped: their "Ar vyko posėdis?" already asks, the overdue digest follows up.
     */
    public static function notifyDaysLeft(int $daysLeft): void
    {
        app(ReservationReviewTaskSynchronizer::class)->reconcileAll();

        $tasks = Task::with('users', 'taskable')->whereDate('due_date', '=', now()->addDays($daysLeft))->where('completed_at', null)
            ->where(fn ($query) => $query->whereNull('action_type')->orWhere('action_type', '!=', ActionType::PeriodicityGap))
            ->get();

        foreach ($tasks as $task) {
            $recipients = $task->notifiableUsers()
                ->filter(fn (User $user): bool => in_array($daysLeft, $user->getTaskReminderDays(), true));

            Notification::send($recipients, new TaskReminderNotification($task, $daysLeft));
        }
    }
}
