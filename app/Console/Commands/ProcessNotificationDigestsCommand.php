<?php

namespace App\Console\Commands;

use App\Mail\NotificationDigest;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class ProcessNotificationDigestsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:process-digests
                            {--force : Process all pending digests regardless of user frequency settings}';

    /**
     * The console command description.
     */
    protected $description = 'Process and send notification digest emails to users based on their frequency preferences';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing notification digests...');

        $force = $this->option('force');

        // Get all users with pending digest items
        $usersWithDigests = NotificationDigestQueue::query()
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($usersWithDigests->isEmpty()) {
            $this->info('No pending digest notifications.');

            return self::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($usersWithDigests as $userId) {
            $user = User::find($userId);

            if (! $user) {
                // User was deleted, clean up their digest items
                NotificationDigestQueue::where('user_id', $userId)->delete();

                continue;
            }

            // Check if it's time to send digest based on user preferences
            if (! $force && ! $this->shouldSendDigest($user)) {
                $skipped++;

                continue;
            }

            // Get all pending digest items for this user
            $digestItems = NotificationDigestQueue::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($digestItems->isEmpty()) {
                continue;
            }

            // Group items by category
            $groupedItems = $digestItems->groupBy('category')->map(function ($items) {
                return $items->map(fn ($item) => $item->data)->all();
            })->all();

            // Send the digest email
            try {
                Mail::to($user)->send(new NotificationDigest($user, $groupedItems));

                // Delete processed items
                NotificationDigestQueue::where('user_id', $userId)
                    ->whereIn('id', $digestItems->pluck('id'))
                    ->delete();

                $processed++;
                $this->line("  Sent digest to {$user->email} ({$digestItems->count()} notifications)");
            } catch (\Exception $e) {
                $this->error("  Failed to send digest to {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info("Processed: {$processed}, Skipped: {$skipped}");

        return self::SUCCESS;
    }

    /**
     * Check if it's time to send a digest to the user based on their preferences.
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

        // Check if enough time has passed since the oldest item
        $threshold = Carbon::now()->subHours($frequencyHours);

        return $oldestItem->created_at->lte($threshold);
    }
}
