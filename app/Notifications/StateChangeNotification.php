<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// NOTE: right now only used for Doing::class
class StateChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $text;

    protected array $objectArray;

    protected array $subjectArray;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $text, $objectArray = null, $subjectArray = null)
    {
        $this->text = $text;
        $this->objectArray = $objectArray;
        $this->subjectArray = $subjectArray;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    // public function toMail($notifiable)
    // {
    // return (new MailMessage)
    //             ->line('The introduction to the notification.')
    //             ->action('Notification Action', url('/'))
    //             ->line('Thank you for using our application!');
    // }

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
            'object' => $this->objectArray,
            'subject' => $this->subjectArray,
        ];
    }
}
