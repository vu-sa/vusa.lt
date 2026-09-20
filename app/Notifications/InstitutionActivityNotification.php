<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationUrgency;
use App\Models\Institution;
use App\Models\Task;

class InstitutionActivityNotification extends BaseNotification
{
    public function __construct(
        private readonly Task $task,
        private readonly Institution $institution,
    ) {}

    public function category(): NotificationCategory
    {
        return NotificationCategory::Task;
    }

    public function urgency(): NotificationUrgency
    {
        return NotificationUrgency::Act;
    }

    #[\Override]
    public function sendsPush(): bool
    {
        return false;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.periodicity_gap_question_title', [
            'institution' => $this->institution->name,
        ]);
    }

    public function body(object $notifiable): string
    {
        $days = $this->task->metadata['effective_days_since_activity'] ?? null;

        if (! is_numeric($days)) {
            return __('notifications.periodicity_gap_body');
        }

        return __('notifications.periodicity_gap_body_days', [
            'institution' => $this->institution->name,
            'days' => (int) $days,
        ]);
    }

    public function url(): string
    {
        return route('institutions.show', $this->institution);
    }

    public function modelClass(): ?string
    {
        return 'Institution';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Institution',
            'name' => $this->institution->name,
            'url' => $this->url(),
            'id' => $this->institution->id,
        ];
    }

    #[\Override]
    public function context(object $notifiable): array
    {
        $days = $this->task->metadata['effective_days_since_activity'] ?? null;

        return $this->contextRows([
            'institution' => $this->institution->name,
            'days_since_activity' => is_numeric($days) ? __('notifications.context.days_value', ['count' => (int) $days]) : null,
        ]);
    }

    #[\Override]
    public function mailSignature(object $notifiable): ?array
    {
        return $this->coordinatorSignature($notifiable, $this->institution);
    }

    /**
     * Both answers open Pradžia with the window already on the right flow (U21, R-a): "yes" records the
     * meeting, "no" files a check-in, which is what closes the task.
     */
    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_register_meeting'),
            'url' => $this->answerUrl('meeting.create'),
        ];
    }

    #[\Override]
    public function secondaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_report_activity'),
            'url' => $this->answerUrl('check-in'),
        ];
    }

    #[\Override]
    public function secondaryActionIsAnswer(): bool
    {
        return true;
    }

    private function answerUrl(string $window): string
    {
        return route('dashboard', ['window' => $window, 'institution' => $this->institution->id]);
    }
}
