<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class UserAttachedToModel extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The model instance.
     */
    public Model $model;

    /**
     * The user instance.
     */
    public User $attacher;

    /**
     * The notification's subject.
     */
    public array $subject;

    /**
     * The notification's object.
     */
    public array $object;

    /**
     * Create a new notification instance.
     */
    public function __construct(Model $model, User $attacher)
    {
        $this->model = $model;
        $this->attacher = $attacher;

        $objectName = optional($model)->name ?: optional($model)->title ?: null;

        $this->subject = [
            'modelClass' => class_basename(get_class($this->attacher)),
            'name' => $this->attacher->name,
            'image' => $this->attacher->profile_photo_path,
        ];

        $this->object = [
            'modelClass' => class_basename(get_class($this->model)),
            'name' => $objectName,
            'url' => route(Str::of(class_basename(get_class($this->model)))->lcfirst()->plural().'.show', $this->model->getKey()),
        ];
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', WebPushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Buvote priskirti prie '.$this->object['name'])
            ->markdown('emails.user-attached-to-model', [
                'attacher' => $this->attacher,
                'subject' => $this->subject,
                'object' => $this->object,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'text' => $this->attacher->name.' priskyrė jus prie '.(optional($this->model)->name ?: optional($this->model)->title ?: 'objekto'),
            'subject' => $this->subject,
            'object' => $this->object,
        ];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $objectName = optional($this->model)->name ?: optional($this->model)->title ?: 'objekto';

        return (new WebPushMessage)
            ->title('🔗 '.__('Priskyrimas'))
            ->icon('/images/icons/favicons/favicon-196x196.png')
            ->body($this->attacher->name.' priskyrė jus prie '.$objectName)
            ->action(__('Peržiūrėti'), 'view')
            ->options(['TTL' => 1000])
            ->data(['url' => $this->object['url'] ?? '/mano']);
    }
}
