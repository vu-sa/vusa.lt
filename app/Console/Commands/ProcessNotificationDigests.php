<?php

namespace App\Console\Commands;

use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Process notification digest queue and send batched email digests.
 *
 * This command runs hourly and checks each user's digest frequency setting
 * to determine if it's time to send their digest.
 */
class ProcessNotificationDigests extends Command
{
    protected $signature = 'notifications:send-digests';

    protected $description = 'Process and send notification email digests based on user preferences';

    public function handle(): int
    {
        $usersWithPendingDigests = NotificationDigestQueue::query()
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($usersWithPendingDigests->isEmpty()) {
            $this->info('No pending digests to process.');

            return self::SUCCESS;
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($usersWithPendingDigests as $userId) {
            $user = User::find($userId);

            if (! $user) {
                // Clean up orphaned digest items
                NotificationDigestQueue::where('user_id', $userId)->delete();

                continue;
            }

            // Check if it's time to send digest based on user's frequency setting
            if (! $this->shouldSendDigest($user)) {
                $skippedCount++;

                continue;
            }

            // Get all pending items for this user
            $digestItems = NotificationDigestQueue::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($digestItems->isEmpty()) {
                continue;
            }

            // Group items by category
            $groupedItems = $digestItems->groupBy('category')
                ->map(fn ($items) => $items->pluck('data')->toArray())
                ->toArray();

            // Send the digest email
            try {
                Mail::to($user)->send(new NotificationDigest($user, $groupedItems));

                // Delete processed items
                NotificationDigestQueue::where('user_id', $userId)
                    ->whereIn('id', $digestItems->pluck('id'))
                    ->delete();

                $sentCount++;
                $this->info("Sent digest to {$user->email} with {$digestItems->count()} notifications.");
            } catch (\Exception $e) {
                $this->error("Failed to send digest to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Processed digests: {$sentCount} sent, {$skippedCount} skipped (not time yet).");

        return self::SUCCESS;
    }

    /**
     * Determine if it's time to send a digest to the user.
     */
    protected function shouldSendDigest(User $user): bool
    {
        $frequencyHours = $user->getDigestFrequencyHours();

        // Get the oldest pending digest item for this user
        $oldestItem = NotificationDigestQueue::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->first();

        if (! $oldestItem) {
            return false;
        }

        // Check if enough time has passed since the oldest item was queued
        $oldestTime = Carbon::parse($oldestItem->created_at);
        $hoursSinceOldest = $oldestTime->diffInHours(now());

        return $hoursSinceOldest >= $frequencyHours;
    }
}
