<?php

namespace App\Console\Commands;

use App\Models\NotificationDigestQueue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Prune stale notification digest items.
 *
 * Items only leave `notification_digest_queue` when a digest is successfully
 * mailed. If digests stop going out (a dead scheduler, a broken mail transport)
 * the queue grows without bound and users would eventually receive a digest of
 * long-obsolete notifications. This command drops items past a cutoff.
 */
#[Description('Prune stale items from the notification digest queue')]
#[Signature('notifications:prune-digests
                            {--older-than=7 : Prune items older than this many days}
                            {--all : Empty the queue entirely, regardless of age}
                            {--dry-run : Show what would be pruned without deleting anything}
                            {--force : Skip confirmation (required for production)}')]
class PruneNotificationDigests extends Command
{
    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $days = (int) $this->option('older-than');

        if (! $all && $days < 1) {
            $this->error('--older-than must be at least 1 day.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN - No items will be deleted');
            $this->newLine();
        }

        // `--all` drops the age condition entirely: nothing queued goes out.
        $cutoff = $all ? null : now()->subDays($days);

        $staleQuery = $this->pendingQuery($cutoff);

        $staleItems = (clone $staleQuery)->count();
        $staleUsers = (clone $staleQuery)->distinct()->count('user_id');

        $this->info($cutoff === null
            ? 'Pruning the entire digest queue.'
            : "Cutoff: {$cutoff->toDateTimeString()} (older than {$days} day(s))");
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Items to prune', $staleItems],
                ['Users affected', $staleUsers],
                ['Items retained', $cutoff === null
                    ? 0
                    : NotificationDigestQueue::query()->where('created_at', '>=', $cutoff)->count()],
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
            $chunk = $this->pendingQuery($cutoff)->limit(1000)->delete();

            $deleted += $chunk;
        } while ($chunk > 0);

        $this->newLine();
        $this->info("✅ Pruned {$deleted} digest item(s).");

        return self::SUCCESS;
    }

    /**
     * Items eligible for pruning. A null cutoff means the whole queue.
     *
     * @return Builder<NotificationDigestQueue>
     */
    private function pendingQuery(?Carbon $cutoff)
    {
        $query = NotificationDigestQueue::query();

        return $cutoff === null ? $query : $query->where('created_at', '<', $cutoff);
    }
}
