<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;
use App\Models\Institution;
use App\Models\Task;
use App\Models\User;

/**
 * Notification sent when a user is assigned to a new task.
 *
 * Replaces the old TaskCreatedNotification with standardized structure.
 */
class TaskAssignedNotification extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(protected Task $task, protected ?User $assigner = null) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    /**
     * Due within a week it asks for action now; further out it is worth knowing, not mailing.
     */
    public function urgency(): NotificationUrgency
    {
        $due = $this->task->due_date;

        return $due !== null && $due->lte(now()->addDays(7)) ? NotificationUrgency::Act : NotificationUrgency::Know;
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

    #[\Override]
    public function context(object $notifiable): array
    {
        $taskable = $this->task->taskable;

        return $this->contextRows([
            'institution' => $taskable instanceof Institution ? $taskable->name : null,
            'deadline' => $this->task->due_date?->format('Y-m-d'),
        ]);
    }

    #[\Override]
    public function mailSignature(object $notifiable): ?array
    {
        return $this->coordinatorSignature($notifiable, $this->task->taskable instanceof Institution ? $this->task->taskable : null);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_tasks'),
            'url' => route('userTasks'),
        ];
    }
}
