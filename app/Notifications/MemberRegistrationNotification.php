<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Institution;
use App\Notifications\Concerns\NamesObjectInTitle;

/**
 * Notification sent when a new member registers.
 */
class MemberRegistrationNotification extends BaseNotification
{
    use NamesObjectInTitle;

    public function type(): NotificationType
    {
        return NotificationType::MemberRegistration;
    }

    public function __construct(protected int $registrationId, protected string $memberName, protected Institution $institution, public string $email, protected string $formId) {}

    public function title(object $notifiable): string
    {
        return __('notifications.member_registered_title', ['name' => $this->memberName]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.member_registered_body', [
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
            'name' => $this->memberName,
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
