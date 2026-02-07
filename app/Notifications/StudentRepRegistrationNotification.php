<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Mail\InformManagerAboutStudentRepRegistration;
use App\Models\Institution;

/**
 * Notification sent when a new student representative registers.
 *
 * Replaces the old StudentRepRegistered notification with standardized structure.
 */
class StudentRepRegistrationNotification extends BaseNotification
{
    protected string $registrationId;

    protected string $repName;

    protected Institution $institution;

    protected string $formId;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $registrationId,
        string $repName,
        Institution $institution,
        string $formId
    ) {
        $this->registrationId = $registrationId;
        $this->repName = $repName;
        $this->institution = $institution;
        $this->formId = $formId;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Registration;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.student_rep_registered_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.student_rep_registered_body', [
            'name' => $this->repName,
            'institution' => $this->institution->getMaybeShortNameAttribute(),
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

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_registration'),
                'url' => route('forms.show', $this->formId),
            ],
        ];
    }

    /**
     * Override via to use custom mail.
     */
    public function via(object $notifiable): array
    {
        $channels = parent::via($notifiable);

        // Always include mail for registration notifications
        if (! in_array('mail', $channels)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Use custom mailable for rich email content.
     */
    public function toMail(object $notifiable): \Illuminate\Contracts\Mail\Mailable
    {
        return (new InformManagerAboutStudentRepRegistration(
            $this->registrationId,
            $this->repName,
            $this->institution,
            $this->formId
        ))->to($notifiable->email);
    }
}
