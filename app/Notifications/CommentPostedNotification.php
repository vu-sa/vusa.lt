<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use Illuminate\Support\Str;

class CommentPostedNotification extends BaseNotification
{
    public function type(): NotificationType
    {
        return $this->isMention ? NotificationType::CommentMention : NotificationType::CommentActivity;
    }

    /**
     * @param  array{modelClass: string, name: string, url: string, id?: string|int}  $commentedObject
     * @param  array{modelClass: string, name: string, image?: string}  $commenter
     */
    public function __construct(
        protected string $commentBody,
        protected array $commentedObject,
        protected array $commenter,
        protected bool $isMention = false,
        protected bool $isPoll = false,
    ) {}

    public function title(object $notifiable): string
    {
        return __($this->isPoll ? 'notifications.poll_posted_title' : 'notifications.comment_posted_title', [
            'name' => $this->commentedObject['name'],
        ]);
    }

    public function body(object $notifiable): string
    {
        $key = match (true) {
            $this->isMention && $this->isPoll => 'notifications.poll_mentioned_body',
            $this->isMention => 'notifications.comment_mentioned_body',
            $this->isPoll => 'notifications.comment_poll_body',
            default => 'notifications.comment_posted_body',
        };

        return __($key, ['commenter' => $this->commenter['name']]);
    }

    public function excerpt(object $notifiable): ?string
    {
        $html = preg_replace('/(<\/(?:p|li|ul|ol|blockquote|div|h[1-6])\s*>|<br\b[^>]*>)/i', ' $1', $this->commentBody);
        $text = Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : Str::limit($text, 200);
    }

    public function digestLine(object $notifiable): string
    {
        $body = $this->body($notifiable).' · '.$this->commentedObject['name'];
        $excerpt = $this->excerpt($notifiable);

        return $excerpt === null ? $body : $body.' — '.__('notifications.quote', ['text' => $excerpt]);
    }

    public function digestGroupTitle(object $notifiable): string
    {
        return __('notifications.comment_digest_group');
    }

    public function url(): string
    {
        return $this->commentedObject['url'];
    }

    public function modelClass(): ?string
    {
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
    public function primaryAction(): ?array
    {
        return [
            'label' => __('notifications.action_view_comment'),
            'url' => $this->url(),
        ];
    }
}
