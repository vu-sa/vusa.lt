<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Notification sent when a comment is posted on a model.
 *
 * Replaces the old ModelCommented notification with standardized structure.
 */
class CommentPostedNotification extends BaseNotification
{
    /**
     * The comment text/content.
     */
    protected string $commentText;

    /**
     * The model that was commented on.
     *
     * @var array{modelClass: string, name: string, url: string, id?: string}
     */
    protected array $commentedObject;

    /**
     * The user who posted the comment.
     *
     * @var array{modelClass: string, name: string, image?: string}
     */
    protected array $commenter;

    /**
     * Create a new notification instance.
     *
     * @param  array{modelClass: string, name: string, url: string, id?: string}  $object
     * @param  array{modelClass: string, name: string, image?: string}  $subject
     */
    public function __construct(string $commentText, array $object, array $subject)
    {
        $this->commentText = $commentText;
        $this->commentedObject = $object;
        $this->commenter = $subject;
    }

    /**
     * Create from a comment model and related data.
     */
    public static function fromComment(string $text, Model $commentable, User $commenter): self
    {
        $objectName = $commentable->name ?? $commentable->title ?? __('objektas');

        $object = [
            'modelClass' => class_basename(get_class($commentable)),
            'name' => $objectName,
            'url' => method_exists($commentable, 'getShowUrl') ? $commentable->getShowUrl() : '#',
            'id' => $commentable->getKey(),
        ];

        $subject = [
            'modelClass' => 'User',
            'name' => $commenter->name,
            'image' => $commenter->profile_photo_path,
        ];

        return new self($text, $object, $subject);
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::Comment;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.comment_posted_title', [
            'name' => $this->commentedObject['name'],
        ]);
    }

    public function body(object $notifiable): string
    {
        return __('notifications.comment_posted_body', [
            'commenter' => $this->commenter['name'],
            'comment' => \Illuminate\Support\Str::limit(strip_tags($this->commentText), 150),
        ]);
    }

    public function url(): string
    {
        return $this->commentedObject['url'] ?? route('dashboard');
    }

    public function modelClass(): ?string
    {
        // Map the commented object's model class to ModelEnum key
        $mapping = [
            'Reservation' => 'RESERVATION',
            'Meeting' => 'MEETING',
            'Task' => 'TASK',
            'Institution' => 'INSTITUTION',
            'Duty' => 'DUTY',
        ];

        return $mapping[$this->commentedObject['modelClass']] ?? null;
    }

    public function subject(): ?array
    {
        return $this->commenter;
    }

    public function object(): ?array
    {
        return $this->commentedObject;
    }

    public function actions(): array
    {
        return [
            [
                'label' => __('notifications.action_view_comment'),
                'url' => $this->url(),
            ],
        ];
    }

    /**
     * Override via to also handle Duty notifiable (mail only).
     */
    public function via(object $notifiable): array
    {
        // If notifiable is a Duty, only send mail
        if (class_basename(get_class($notifiable)) === 'Duty') {
            return ['mail'];
        }

        return parent::via($notifiable);
    }

    /**
     * Custom mail for better formatting.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->icon().' '.__('notifications.comment_posted_title', ['name' => $this->commentedObject['name']]))
            ->markdown('emails.comment-posted', [
                'commentText' => $this->commentText,
                'object' => $this->commentedObject,
                'commenter' => $this->commenter,
            ]);
    }
}
