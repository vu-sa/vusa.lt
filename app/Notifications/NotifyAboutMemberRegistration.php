<?php

namespace App\Notifications;

use App\Mail\InformChairAboutMemberRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NotifyAboutMemberRegistration extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data, $registerLocation, $chairEmail)
    {
        $this->data = $data;
        $this->registerLocation = $registerLocation;
        $this->email = $chairEmail;
    }

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
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new InformChairAboutMemberRegistration($this->data, $this->registerLocation))
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
            'registeredName' => $this->data['name'],
            'route' => ["routeName" => 'registrationForms.show', "model" => 2]
        ];
    }
}
