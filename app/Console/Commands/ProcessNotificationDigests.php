<?php

namespace App\Console\Commands;

use App\Enums\EmailDelivery;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\NotificationType;
use App\Mail\NotificationDigest;
use App\Models\InstitutionActivityRequest;
use App\Models\NotificationDigestQueue;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Support\QuietHours;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Process notification digest queue and send batched email digests.
 *
 * This command runs hourly and checks each user's digest frequency setting
 * to determine if it's time to send their digest. Nothing is sent during quiet hours
 * (22:00–07:00); the queue simply waits for the first run after they end.
 */
#[Description('Process and send notification email digests based on user preferences')]
#[Signature('notifications:send-digests')]
class ProcessNotificationDigests extends Command
{
    public function handle(): int
    {
        if (QuietHours::isQuiet(now())) {
            $this->info('Quiet hours — digests are held until 07:00.');

            return self::SUCCESS;
        }

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
        $failedCount = 0;

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

            $activityItems = $digestItems->where('notification_class', InstitutionActivityNotification::class);
            $requestIds = $activityItems->flatMap(fn ($item) => $item->data['activity_request_ids'] ?? [])->unique();
            $activityRequests = InstitutionActivityRequest::query()->open()->whereKey($requestIds)
                ->where('recipient_id', $user->id)->whereHas('institution')->whereHas('recipient')
                ->with(['institution.meetings', 'requestedBy', 'task'])->get()->keyBy('id');
            $activityRequests->where('campaign_type', InstitutionActivityCampaign::MissingMeetings)
                ->loadMissing(['institution.meetings.agendaItems.votes', 'institution.meetings.institutions']);
            $digestItems = $digestItems->filter(function ($item) use ($user, $activityRequests): bool {
                if ($item->notification_class !== InstitutionActivityNotification::class || ! isset($item->data['activity_request_ids'])) {
                    return true;
                }
                $requests = $activityRequests->only($item->data['activity_request_ids'])->values()->reject(fn ($request) => $request->campaign_type === InstitutionActivityCampaign::MissingMeetings
                    ? $request->incompleteMeetings()->isEmpty()
                    : $request->institution->meetings->contains(fn ($meeting) => $meeting->start_time->toDateString() >= $request->period_start->toDateString()
                        && $meeting->start_time->toDateString() <= $request->periodEnd()->toDateString()));
                if ($requests->isEmpty() || $user->isGloballyMuted() || $user->emailDeliveryFor(NotificationType::InstitutionActivity) !== EmailDelivery::Digest) {
                    $item->delete();

                    return false;
                }
                $item->data = new InstitutionActivityNotification(new Collection($requests->all()))->toDigestItem($user);

                return true;
            });

            if ($digestItems->isEmpty()) {
                continue;
            }

            // Group items by category
            $groupedItems = $digestItems->groupBy('category')
                ->map(fn ($items) => $items->pluck('data')->toArray())
                ->toArray();

            // Send the digest email.
            //
            // sendNow() is deliberate: NotificationDigest is a ShouldQueue mailable, so
            // send() would merely enqueue it and return successfully. The items below
            // would then be deleted before delivery was ever attempted, and a transport
            // failure in the worker would destroy them. Sending synchronously means a
            // failure leaves the items queued for the next run.
            try {
                $digestEmails = $user->notificationEmails();
                Mail::to($digestEmails)->sendNow(new NotificationDigest($user, $groupedItems));

                // Delete processed items
                NotificationDigestQueue::where('user_id', $userId)
                    ->whereIn('id', $digestItems->pluck('id'))
                    ->delete();

                $sentCount++;
                $this->info('Sent digest to '.implode(', ', $digestEmails)." with {$digestItems->count()} notifications.");
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Failed to send digest to {$user->email}: {$e->getMessage()}");

                // The command runs from cron, where console output goes nowhere.
                Log::error('Failed to send notification digest', [
                    'user_id' => $userId,
                    'items' => $digestItems->count(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Processed digests: {$sentCount} sent, {$skippedCount} skipped (not time yet), {$failedCount} failed.");

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
