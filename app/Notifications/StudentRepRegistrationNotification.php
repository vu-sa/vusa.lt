<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Institution;
use App\Notifications\Concerns\NamesObjectInTitle;

/**
 * Notification sent when a new student representative registers.
 */
class StudentRepRegistrationNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::StudentRepRegistration;
    }

    public function __construct(protected string $registrationId, protected string $repName, protected Institution $institution, protected string $formId) {}

    public function title(object $notifiable): string
    {
        return __('notifications.student_rep_registered_title', ['name' => $this->repName]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.student_rep_registered_body', [
            'institution' => $this->institution->name,
        ]);
    }

    public function url(): string
    {
        return route('forms.show', $this->formId);
    }

    public function modelClass(): ?string
    {
        return 'FORM';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'Form',
            'name' => $this->repName,
            'url' => route('forms.show', $this->formId),
            'id' => $this->formId,
        ];
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_registration'),
            'url' => route('forms.show', $this->formId),
        ];
    }
}
