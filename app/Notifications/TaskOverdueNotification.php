<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use Illuminate\Support\Collection;

/**
 * Weekly notification sent to users who have overdue tasks.
 *
 * This notification is sent once per week with a summary of all overdue tasks.
 */
class TaskOverdueNotification extends BaseNotification
{
    /**
     * The collection of overdue tasks.
     *
     * @var Collection<\App\Models\Task>
     */
    protected Collection $tasks;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<\App\Models\Task>  $tasks
     */
    public function __construct(Collection $tasks)
    {
        $this->tasks = $tasks;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.task_overdue_title', ['count' => $this->tasks->count()]);
    }

    public function body(object $notifiable): string
    {
        if ($this->tasks->count() === 1) {
            return __('notifications.task_overdue_body_single', [
                'task' => $this->tasks->first()->name,
            ]);
        }

        return __('notifications.task_overdue_body_multiple', [
            'count' => $this->tasks->count(),
            'tasks' => $this->tasks->take(3)->pluck('name')->join(', '),
        ]);
    }

    public function url(): string
    {
        return route('userTasks');
    }

    public function icon(): string
    {
        return '⚠️';
    }

    public function modelClass(): ?string
    {
        return 'TASK';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Task',
            'name' => __('notifications.overdue_tasks'),
            'url' => route('userTasks'),
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
     * Overdue notifications are important and should not be digested.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
