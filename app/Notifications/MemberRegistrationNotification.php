<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Mail\InformChairAboutMemberRegistration;
use App\Models\Institution;

/**
 * Notification sent when a new member registers.
 *
 * Replaces the old MemberRegistered notification with standardized structure.
 */
class MemberRegistrationNotification extends BaseNotification
{
    protected int $registrationId;

    protected string $memberName;

    protected Institution $institution;

    protected string $email;

    protected string $formId;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        int $registrationId,
        string $memberName,
        Institution $institution,
        string $email,
        string $formId
    ) {
        $this->registrationId = $registrationId;
        $this->memberName = $memberName;
        $this->institution = $institution;
        $this->email = $email;
        $this->formId = $formId;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Registration;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.member_registered_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.member_registered_body', [
            'name' => $this->memberName,
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
            'name' => $this->memberName,
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
        return (new InformChairAboutMemberRegistration(
            (string) $this->registrationId,
            $this->memberName,
            $this->institution,
            $this->formId
        ))->to($this->email);
    }
}
