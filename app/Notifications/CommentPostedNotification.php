<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Notification sent when a comment is posted on a model.
 *
 * Replaces the old ModelCommented notification with standardized structure.
 */
class CommentPostedNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return $this->isMention ? NotificationType::CommentMention : NotificationType::CommentActivity;
    }

    /**
     * Create a new notification instance.
     *
     * @param  array{modelClass: string, name: string, url: string, id?: string}  $commentedObject
     * @param  array{modelClass: string, name: string, image?: string}  $commenter
     */
    public function __construct(
        /**
         * The comment text/content.
         */
        protected string $commentText,
        /**
         * The model that was commented on.
         */
        protected array $commentedObject,
        /**
         * The user who posted the comment.
         */
        protected array $commenter,
        /**
         * Whether the recipient was @-mentioned (asks for a reply) rather than following the thread.
         */
        protected bool $isMention = false
    ) {}

    /**
     * Create from a comment model and related data.
     */
    public static function fromComment(string $text, Model $commentable, User $commenter): self
    {
        $objectName = $commentable->name ?? $commentable->title ?? __('objektas');

        $object = [
            'modelClass' => class_basename($commentable::class),
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
            'comment' => Str::limit(strip_tags($this->commentText), 150),
        ]);
    }

    public function url(): string
    {
        return $this->commentedObject['url'];
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

    #[\Override]
    public function context(object $notifiable): array
    {
        return $this->contextRows([
            'object' => $this->commentedObject['name'],
            'author' => $this->commenter['name'],
        ]);
    }

    #[\Override]
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_comment'),
            'url' => $this->url(),
        ];
    }
}
