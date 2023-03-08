<?php

namespace App\Notifications;

use App\Mail\InformChairAboutMemberRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MemberRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $data;
    protected $registerLocation;
    protected $email;

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
            'objectName' => $this->data['name'],
            'actionUrl' => ['routeName' => 'registrationForms.show', 'model' => 2],
        ];
    }
}
