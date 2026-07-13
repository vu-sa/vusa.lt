<?php

namespace App\Console\Commands;

use App\Models\NotificationDigestQueue;
use Illuminate\Console\Command;

/**
 * Prune stale notification digest items.
 *
 * Items only leave `notification_digest_queue` when a digest is successfully
 * mailed. If digests stop going out (a dead scheduler, a broken mail transport)
 * the queue grows without bound and users would eventually receive a digest of
 * long-obsolete notifications. This command drops items past a cutoff.
 */
class PruneNotificationDigests extends Command
{
    protected $signature = 'notifications:prune-digests
                            {--older-than=7 : Prune items older than this many days}
                            {--dry-run : Show what would be pruned without deleting anything}
                            {--force : Skip confirmation (required for production)}';

    protected $description = 'Prune stale items from the notification digest queue';

    public function handle(): int
    {
        $days = (int) $this->option('older-than');

        if ($days < 1) {
            $this->error('--older-than must be at least 1 day.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN - No items will be deleted');
            $this->newLine();
        }

        $cutoff = now()->subDays($days);

        $staleQuery = NotificationDigestQueue::query()->where('created_at', '<', $cutoff);

        $staleItems = (clone $staleQuery)->count();
        $staleUsers = (clone $staleQuery)->distinct()->count('user_id');

        $this->info("Cutoff: {$cutoff->toDateTimeString()} (older than {$days} day(s))");
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Items to prune', $staleItems],
                ['Users affected', $staleUsers],
                ['Items retained', NotificationDigestQueue::query()->where('created_at', '>=', $cutoff)->count()],
            ]
        );

        if ($staleItems === 0) {
            $this->info('Nothing to prune.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Permanently delete {$staleItems} digest item(s)?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $deleted = 0;

        do {
            $chunk = NotificationDigestQueue::query()
                ->where('created_at', '<', $cutoff)
                ->limit(1000)
                ->delete();

            $deleted += $chunk;
        } while ($chunk > 0);

        $this->newLine();
        $this->info("✅ Pruned {$deleted} digest item(s).");

        return self::SUCCESS;
    }
}
