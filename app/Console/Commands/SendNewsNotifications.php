<?php

namespace App\Console\Commands;

use App\Enums\NotificationCategory;
use App\Enums\NotificationChannel;
use App\Models\News;
use App\Models\User;
use App\Notifications\NewsPublishedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Send news notifications for recently published articles.
 *
 * This command finds news articles that have been published (not draft,
 * publish_time has passed) within the last run window and notifies
 * users who have opted in to news notifications.
 */
class SendNewsNotifications extends Command
{
    protected $signature = 'notifications:send-news';

    protected $description = 'Send notifications for recently published news articles';

    public function handle(): int
    {
        $sentCount = 0;

        // Get news published in the last 15 minutes (matches the scheduling interval)
        $recentlyPublishedNews = $this->getRecentlyPublishedNews();

        if ($recentlyPublishedNews->isEmpty()) {
            $this->info('No recently published news found.');

            return self::SUCCESS;
        }

        // Get users who have opted in to news notifications
        $usersToNotify = $this->getUsersOptedInForNews();

        if ($usersToNotify->isEmpty()) {
            $this->info('No users have opted in for news notifications.');

            return self::SUCCESS;
        }

        foreach ($recentlyPublishedNews as $news) {
            foreach ($usersToNotify as $user) {
                $user->notify(new NewsPublishedNotification($news));
                $sentCount++;
            }
        }

        $this->info("Sent {$sentCount} news notifications for {$recentlyPublishedNews->count()} article(s).");

        return self::SUCCESS;
    }

    /**
     * Get news articles published in the last 15 minutes.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\News>
     */
    protected function getRecentlyPublishedNews(): \Illuminate\Database\Eloquent\Collection
    {
        $windowStart = Carbon::now()->subMinutes(15);
        $windowEnd = Carbon::now();

        return News::query()
            ->with('tenant')
            ->where('draft', false)
            ->whereBetween('publish_time', [$windowStart, $windowEnd])
            ->get();
    }

    /**
     * Get users who have opted in to news notifications.
     */
    protected function getUsersOptedInForNews(): \Illuminate\Support\Collection
    {
        return User::all()->filter(function (User $user) {
            // Check if user has enabled at least one channel for news
            return $user->shouldReceiveNotification(NotificationCategory::News, NotificationChannel::InApp)
                || $user->shouldReceiveNotification(NotificationCategory::News, NotificationChannel::Push);
        });
    }
}
