<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderToLoginNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct() {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $institutions = $notifiable->current_duties->map(function ($duty) {
            return $duty->institution;
        });
        
        return (new MailMessage)->markdown('mail.reminder-to-login-notification', [
            'addressivizedName' => $notifiable->addressivizedName(),
            'institutionLtNames' => $institutions->map(function ($institution) {
                return $institution?->getTranslation('name', 'lt');
            })->filter()->values(),
            'institutionEnNames' => $institutions->map(function ($institution) {
                return $institution?->getTranslation('name', 'en');
            })->filter()->values(),
        ])->replyTo('it@vusa.lt')->subject('📢 Primename apie atstovavimo procesą | Reminding about the representation process');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
