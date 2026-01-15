<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Task;

/**
 * Notification sent to task assignees when a task is auto-completed by the system.
 *
 * This is different from TaskCompletedNotification which is for user-completed tasks.
 * Auto-completed tasks include approval tasks (when decision is made), pickup tasks
 * (when resource is lent), and return tasks (when resource is returned).
 */
class TaskAutoCompletedNotification extends BaseNotification
{
    protected Task $task;

    protected string $completionReason;

    /**
     * Create a new notification instance.
     *
     * @param  string  $completionReason  Human-readable reason for completion
     */
    public function __construct(Task $task, string $completionReason)
    {
        $this->task = $task;
        $this->completionReason = $completionReason;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.task_auto_completed_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.task_auto_completed_body', [
            'task' => $this->task->name,
            'reason' => $this->completionReason,
        ]);
    }

    public function url(): string
    {
        // Link to the taskable if available, otherwise to tasks list
        if ($this->task->taskable_type && $this->task->taskable_id) {
            return $this->getTaskableUrl();
        }

        return route('userTasks');
    }

    public function icon(): string
    {
        return match ($this->task->action_type?->value) {
            'approval' => '✅',
            'pickup' => '📦',
            'return' => '🔄',
            default => '✓',
        };
    }

    public function modelClass(): ?string
    {
        return 'TASK';
    }

    public function subject(): ?array
    {
        return [
            'modelClass' => 'System',
            'name' => __('System'),
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
        $actions = [
            [
                'label' => __('View Details'),
                'url' => $this->url(),
            ],
        ];

        return $actions;
    }

    /**
     * Get URL for the taskable model.
     */
    protected function getTaskableUrl(): string
    {
        $type = class_basename($this->task->taskable_type);

        return match ($type) {
            'Reservation' => route('reservations.show', $this->task->taskable_id),
            'Meeting' => route('meetings.show', $this->task->taskable_id),
            default => route('userTasks'),
        };
    }
}
