<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Send weekly notifications to users with overdue tasks.
 *
 * This command runs once per week (e.g., Monday morning) and notifies
 * users who have tasks that are past their due date and not completed.
 */
class SendTaskOverdueReminders extends Command
{
    protected $signature = 'notifications:task-overdue-reminders';

    protected $description = 'Send weekly reminders to users with overdue tasks';

    public function handle(): int
    {
        // Get all overdue tasks grouped by user
        $overdueTasks = Task::query()
            ->with('users')
            ->where('due_date', '<', Carbon::now())
            ->whereNull('completed_at')
            ->get();

        // Group tasks by user
        $tasksByUser = [];
        foreach ($overdueTasks as $task) {
            foreach ($task->users as $user) {
                $tasksByUser[$user->id][] = $task;
            }
        }

        $sentCount = 0;

        foreach ($tasksByUser as $userId => $tasks) {
            $user = User::find($userId);

            if (! $user) {
                continue;
            }

            $user->notify(new TaskOverdueNotification(collect($tasks)));
            $sentCount++;

            $this->info("Sent overdue task reminder to {$user->email} for ".count($tasks).' tasks.');
        }

        $this->info("Sent {$sentCount} overdue task reminder notifications.");

        return self::SUCCESS;
    }
}
