<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReflectNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $text;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->text = '<p>👋 Kartais reikia skirti laiko pažvelgti į dalykus iš šono – <strong>nepamiršk pareflektuoti</strong>!</p>';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('Atėjo laikas pareflektuoti!')
                    ->action('Pasižiūrėk čia', url('/'))
                    ->line('Refleksija yra svarbu.');
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
            'text' => $this->text,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'text' => $this->text,
        ]);
    }
}
