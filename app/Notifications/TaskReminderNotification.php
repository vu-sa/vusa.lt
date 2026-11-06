<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Task;
use App\Notifications\Concerns\NamesObjectInTitle;

/**
 * Notification sent to remind users about upcoming task deadlines.
 */
class TaskReminderNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::TaskReminder;
    }

    public function __construct(protected Task $task, protected int $daysLeft) {}

    public function title(object $notifiable): string
    {
        return __('notifications.task_reminder_title', ['task' => $this->task->name]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.task_reminder_body', [
            'days' => $this->daysLeft,
        ]);
    }

    public function url(): string
    {
        return route('tasks.index');
    }

    #[\Override]
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
            'url' => route('tasks.index'),
            'id' => $this->task->id,
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'deadline' => $this->task->due_date?->format('Y-m-d'),
        ]);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_tasks'),
            'url' => route('tasks.index'),
        ];
    }
}
