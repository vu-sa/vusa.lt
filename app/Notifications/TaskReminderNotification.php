<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Task;

/**
 * Notification sent to remind users about upcoming task deadlines.
 */
class TaskReminderNotification extends BaseNotification
{
    protected Task $task;

    protected int $daysLeft;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, int $daysLeft)
    {
        $this->task = $task;
        $this->daysLeft = $daysLeft;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.task_reminder_title', ['days' => $this->daysLeft]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.task_reminder_body', [
            'days' => $this->daysLeft,
            'task' => $this->task->name,
        ]);
    }

    public function url(): string
    {
        return route('userTasks');
    }

    public function icon(): string
    {
        return $this->daysLeft <= 1 ? '⚠️' : '⏰';
    }

    public function modelClass(): ?string
    {
        return 'TASK';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Task',
            'name' => $this->task->name,
            'url' => route('userTasks'),
            'id' => $this->task->id,
        ];
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_tasks'),
                'url' => route('userTasks'),
            ],
        ];
    }

    /**
     * Task reminders should not be batched - they are time-sensitive.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
