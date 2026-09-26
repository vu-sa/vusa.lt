<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Mail\InformManagerAboutStudentRepRegistration;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;

/**
 * Notification sent when a new student representative registers.
 *
 * Replaces the old StudentRepRegistered notification with standardized structure.
 */
class StudentRepRegistrationNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return NotificationType::StudentRepRegistration;
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $registrationId, protected string $repName, protected Institution $institution, protected string $formId) {}

    public function title(object $notifiable): string
    {
        return __('notifications.student_rep_registered_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.student_rep_registered_body', [
            'name' => $this->repName,
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

    /**
     * Use custom mailable for rich email content.
     */
    #[\Override]
    public function toMail(object $notifiable): Mailable
    {
        return new InformManagerAboutStudentRepRegistration(
            $this->registrationId,
            $this->repName,
            $this->institution,
            $this->formId
        )->to($notifiable instanceof User ? $notifiable->notificationEmails() : $notifiable->email);
    }
}
