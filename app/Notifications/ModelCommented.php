<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModelCommented extends Notification implements ShouldQueue
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
        return ['database', 'broadcast'];
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
            'object' => $this->objectArray,
            'subject' => $this->subjectArray,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'text' => $this->text,
            'object' => $this->objectArray,
            'subject' => $this->subjectArray
        ]);
    }
}
