<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Mail\InformChairAboutMemberRegistration;
use App\Models\Institution;
use Illuminate\Contracts\Mail\Mailable;

/**
 * Notification sent when a new member registers.
 *
 * Replaces the old MemberRegistered notification with standardized structure.
 */
class MemberRegistrationNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return NotificationType::MemberRegistration;
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(protected int $registrationId, protected string $memberName, protected Institution $institution, protected string $email, protected string $formId) {}

    public function title(object $notifiable): string
    {
        return __('notifications.member_registered_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.member_registered_body', [
            'name' => $this->memberName,
            'institution' => $this->institution->maybe_short_name,
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

    /**
     * Use custom mailable for rich email content.
     */
    #[\Override]
    public function toMail(object $notifiable): Mailable
    {
        return new InformChairAboutMemberRegistration(
            (string) $this->registrationId,
            $this->memberName,
            $this->institution,
            $this->formId
        )->to($this->email);
    }
}
