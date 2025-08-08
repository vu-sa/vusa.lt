<?php

namespace App\Notifications;

use App\Mail\InformChairAboutMemberRegistration;
use App\Models\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(protected int $registration_id, protected string $name, protected Institution $institution, protected string $email) {}

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \App\Mail\InformChairAboutMemberRegistration
     */
    public function toMail($notifiable)
    {
        return (new InformChairAboutMemberRegistration((string) $this->registration_id, $this->name, $this->institution))
            ->to($this->email);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'objectName' => $this->name,
            'actionUrl' => ['routeName' => 'registrations.show', 'model' => $this->registration_id],
        ];
    }
}
