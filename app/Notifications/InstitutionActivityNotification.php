<?php

namespace App\Notifications;

use App\Enums\InstitutionActivityStatus;
use App\Enums\NotificationCategory;
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

    public function title(object $notifiable): string
    {
        $status = InstitutionActivityStatus::tryFrom(
            (string) ($this->task->metadata['activity_status'] ?? '')
        );

        return __('visak.activity.activity_status.'.match ($status) {
            InstitutionActivityStatus::Approaching => 'approaching',
            default => 'overdue',
        });
    }

    public function body(object $notifiable): string
    {
        return __('notifications.periodicity_gap_body', [
            'institution' => $this->institution->name,
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

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_register_meeting'),
                'url' => route('institutions.show', [
                    'institution' => $this->institution,
                    'activityAction' => 'register-meeting',
                ]),
            ],
            [
                'label' => __('notifications.action_report_activity'),
                'url' => route('institutions.show', [
                    'institution' => $this->institution,
                    'activityAction' => 'report-activity',
                ]),
            ],
        ];
    }
}
