<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class TestPushNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', WebPushChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'text' => __('Tai yra bandomasis pranešimas! Push pranešimai veikia.'),
            'subject' => [
                'modelClass' => 'System',
                'name' => 'VU SA Mano',
            ],
            'object' => [
                'modelClass' => 'Test',
                'name' => __('Bandomasis pranešimas'),
                'url' => route('profile'),
            ],
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('🔔 '.__('Bandomasis pranešimas'))
            ->icon('/images/icons/favicons/favicon-196x196.png')
            ->body(__('Push pranešimai veikia! Tai yra bandomasis pranešimas iš VU SA Mano.'))
            ->action(__('Atidaryti'), 'open')
            ->options(['TTL' => 1000])
            ->data(['url' => route('profile')]);
    }
}
