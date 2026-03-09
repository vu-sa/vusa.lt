<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Task;
use App\Models\User;

/**
 * Notification sent to task creator when a task is completed.
 */
class TaskCompletedNotification extends BaseNotification
{
    protected Task $task;

    protected User $completedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, User $completedBy)
    {
        $this->task = $task;
        $this->completedBy = $completedBy;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.task_completed_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.task_completed_body', [
            'task' => $this->task->name,
            'user' => $this->completedBy->name,
        ]);
    }

    public function url(): string
    {
        return route('userTasks');
    }

    public function icon(): string
    {
        return '✅';
    }

    public function modelClass(): ?string
    {
        return 'TASK';
    }

    public function subject(): ?array
    {
        return [
            'modelClass' => 'User',
            'name' => $this->completedBy->name,
            'image' => $this->completedBy->profile_photo_path,
        ];
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
}
