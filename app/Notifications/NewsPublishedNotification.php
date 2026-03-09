<?php

namespace App\Notifications;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\News;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Notification sent when a news article is published.
 * This notification is opt-in (disabled by default).
 */
class NewsPublishedNotification extends BaseNotification
{
    protected News $news;

    /**
     * Create a new notification instance.
     */
    public function __construct(News $news)
    {
        $this->news = $news;
    }

    public function category(): NotificationCategory
    {
        return NotificationCategory::News;
    }

    public function title(object $notifiable): string
    {
        return __('notifications.news_published_title');
    }

    public function body(object $notifiable): string
    {
        return __('notifications.news_published_body', [
            'title' => $this->news->title,
            'tenant' => $this->news->tenant->shortname,
        ]);
    }

    public function url(): string
    {
        $prefix = $this->news->lang === 'lt' ? '/naujiena/' : '/news/';

        return url($prefix.$this->news->permalink);
    }

    public function icon(): string
    {
        return '📰';
    }

    public function modelClass(): ?string
    {
        return 'NEWS';
    }

    public function object(): ?array
    {
        return [
            'modelClass' => 'News',
            'name' => $this->news->title,
            'url' => $this->url(),
            'id' => $this->news->id,
        ];
    }

    /**
     * Do not support email digest for now.
     */
    public function supportsEmailDigest(): bool
    {
        return false;
    }

    /**
     * Get the notification's delivery channels.
     * Only database, broadcast, and webpush - no email.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check if notifications are globally muted for this user
        if (method_exists($notifiable, 'isGloballyMuted') && $notifiable->isGloballyMuted()) {
            return [];
        }

        // Check if user has news notifications enabled
        if (method_exists($notifiable, 'shouldReceiveNotification')) {
            $channels = [];

            if ($notifiable->shouldReceiveNotification($this->category(), NotificationChannel::InApp)) {
                $channels[] = 'database';
                $channels[] = 'broadcast';
            }

            if ($notifiable->shouldReceiveNotification($this->category(), NotificationChannel::Push)) {
                $channels[] = WebPushChannel::class;
            }

            return $channels;
        }

        // Default: database for persistence, broadcast for real-time, webpush for offline
        return ['database', 'broadcast', WebPushChannel::class];
    }
}
