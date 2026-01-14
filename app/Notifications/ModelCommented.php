<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

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
        // if notifiable class is user, then send notification via database, broadcast, mail, and webpush
        if (class_basename(get_class($notifiable)) === 'User') {
            return ['database', 'broadcast', 'mail', WebPushChannel::class];
        }

        // if notifiable class is duty, then send notification via mail
        if (class_basename(get_class($notifiable)) === 'Duty') {
            return ['mail'];
        }

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
            'subject' => $this->subjectArray,
        ]);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->markdown('emails.model-commented', [
                'text' => $this->text,
                'object' => $this->objectArray,
                'subject' => $this->subjectArray,
            ])->subject('💬 '.__('New Comment on').' '.$this->objectArray['name']);
    }

    public function toWebPush($notifiable, $notification)
    {
        return (new WebPushMessage)
            ->title('💬 '.__('Naujas komentaras'))
            ->icon('/images/icons/favicons/favicon-196x196.png')
            ->body($this->text)
            ->action(__('Peržiūrėti'), 'view')
            ->options(['TTL' => 1000])
            ->data(['url' => $this->objectArray['url'] ?? '/mano']);
    }
}
