<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use App\Tasks\Synchronizers\ReservationReviewTaskSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sends full overdue lists weekly when something became overdue in the last seven days,
 * otherwise at most once every four weeks.
 */
#[Description('Send overdue summaries for new overdue tasks or after four weeks')]
#[Signature('notifications:task-overdue-reminders')]
class SendTaskOverdueReminders extends Command
{
    public function handle(): int
    {
        app(ReservationReviewTaskSynchronizer::class)->reconcileAll();

        $overdueTasks = Task::query()
            ->with('users', 'taskable')
            ->where('due_date', '<', Carbon::now())
            ->whereNull('completed_at')
            ->get();

        $tasksByUser = [];
        foreach ($overdueTasks as $task) {
            foreach ($task->notifiableUsers() as $user) {
                $tasksByUser[$user->id][] = $task;
            }
        }

        $recentRecipients = DB::table('notifications')
            ->where('type', TaskOverdueNotification::class)
            ->where('notifiable_type', (new User)->getMorphClass())
            ->whereIn('notifiable_id', array_keys($tasksByUser))
            ->where('created_at', '>=', now()->subDays(27))
            ->pluck('notifiable_id')->flip();
        $users = User::whereKey(array_keys($tasksByUser))->get()->keyBy('id');
        $newSince = now()->subDays(7);
        $sentCount = 0;

        foreach ($tasksByUser as $userId => $tasks) {
            $hasNewTask = collect($tasks)->contains(fn (Task $task): bool => $task->due_date->max($task->created_at)->gte($newSince));
            $user = $users->get($userId);

            if (! $user || (! $hasNewTask && $recentRecipients->has($userId))) {
                continue;
            }

            $user->notify(new TaskOverdueNotification(collect($tasks)));
            $sentCount++;
        }

        $this->info("Sent {$sentCount} overdue task summaries using recipient delivery preferences.");

        return self::SUCCESS;
    }
}
