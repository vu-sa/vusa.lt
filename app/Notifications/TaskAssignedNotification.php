<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Task;
use App\Models\User;

/**
 * Notification sent when a user is assigned to a new task.
 *
 * Replaces the old TaskCreatedNotification with standardized structure.
 */
class TaskAssignedNotification extends BaseNotification
{
    protected Task $task;

    protected ?User $assigner;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task, ?User $assigner = null)
    {
        $this->task = $task;
        $this->assigner = $assigner;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.task_assigned_title');
    }

    public function body(object $notifiable): string
    {
        if ($this->assigner) {
            return __('notifications.task_assigned_body_with_assigner', [
                'assigner' => $this->assigner->name,
                'task' => $this->task->name,
            ]);
        }

        return __('notifications.task_assigned_body', [
            'task' => $this->task->name,
        ]);
    }

    public function url(): string
    {
        return route('userTasks');
    }

    public function modelClass(): ?string
    {
        return 'TASK';
    }

    public function subject(): ?array
    {
        if (! $this->assigner) {
            return null;
        }

        return [
            'modelClass' => 'User',
            'name' => $this->assigner->name,
            'image' => $this->assigner->profile_photo_path,
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
