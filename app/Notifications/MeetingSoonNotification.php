<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $meeting;

    protected $meetingName;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($meeting)
    {
        $this->meeting = $meeting;
        $this->meetingName = optional($this->meeting->institutions)[0]->name;
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
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Žiūrėti susitikimą', route('meetings.show', $this->meeting->id))
                    ->line('Thank you for using our application!');
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
            'text' => $this->meetingName.' susitikimas prasidės už 2 dienų.',
            'object' => [
                'modelClass' => 'Meeting',
                'name' => $this->meeting->name,
                'url' => route('meetings.show', $this->meeting->id),
            ],
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'text' => $this->meetingName.'susitikimas prasidės už 2 dienų.',
            'object' => [
                'modelClass' => 'Meeting',
                'name' => $this->meeting->name,
                'url' => route('meetings.show', $this->meeting->id),
            ],
        ]);
    }
}
