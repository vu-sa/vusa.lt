<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Weekly notification sent to users who have overdue tasks.
 *
 * This notification is sent once per week with a summary of all overdue tasks.
 */
class TaskOverdueNotification extends BaseNotification
{
    /**
     * Create a new notification instance.
     *
     * @param  Collection<Task>  $tasks
     */
    public function __construct(
        /**
         * The collection of overdue tasks.
         */
        protected Collection $tasks
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function title(object $notifiable): string
    {
        return trans_choice('notifications.task_overdue_title', $this->tasks->count(), ['count' => $this->tasks->count()]);
    }

    public function body(object $notifiable): string
    {
        if ($this->tasks->count() === 1) {
            return __('notifications.task_overdue_body_single', [
                'task' => $this->tasks->first()->name ?? '',
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

    #[\Override]
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

    #[\Override]
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
    #[\Override]
    public function supportsEmailDigest(): bool
    {
        return false;
    }
}
