<?php

namespace App\Console\Commands;

use App\Actions\ActivityRequests\ResolveActivityRequestEligibility;
use App\Actions\BuildNotificationDigest;
use App\Enums\EmailDelivery;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\NotificationType;
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
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Runs hourly: daily digests follow the chosen local hour; four-hour digests use queue age.
 * Quiet hours hold all digests until 07:00.
 */
#[Description('Process and send notification email digests based on user preferences')]
#[Signature('notifications:send-digests')]
class ProcessNotificationDigests extends Command
{
    public function handle(ResolveActivityRequestEligibility $eligibility, BuildNotificationDigest $builder): int
    {
        if (QuietHours::isQuiet(now())) {
            $this->info('Quiet hours — digests are held until 07:00.');

            return self::SUCCESS;
        }

        $pendingByUser = NotificationDigestQueue::query()
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('user_id');

        if ($pendingByUser->isEmpty()) {
            $this->info('No pending digests to process.');

            return self::SUCCESS;
        }

        $users = User::query()
            ->whereKey($pendingByUser->keys())
            ->with('current_duties')
            ->get()
            ->keyBy('id');
        $sentCount = 0;
        $skippedCount = 0;
        $failedCount = 0;

        foreach ($pendingByUser as $userId => $digestItems) {
            $user = $users->get($userId);

            if (! $user) {
                // Clean up orphaned digest items
                NotificationDigestQueue::where('user_id', $userId)->delete();

                continue;
            }

            // Check if it's time to send digest based on user's frequency setting
            if (! $this->shouldSendDigest($user, $digestItems)) {
                $skippedCount++;

                continue;
            }

            $digestItems = $this->refreshActivityRequests($digestItems, $user, $eligibility);

            if ($digestItems->isEmpty()) {
                continue;
            }

            // sendNow surfaces transport failures before removing the queued items.
            try {
                $digestEmails = $user->notificationEmails();
                Mail::to($digestEmails)->sendNow($builder->execute($user, $digestItems));

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
     * @param  SupportCollection<int, NotificationDigestQueue>  $digestItems
     * @return SupportCollection<int, NotificationDigestQueue>
     */
    private function refreshActivityRequests(SupportCollection $digestItems, User $user, ResolveActivityRequestEligibility $eligibility): SupportCollection
    {
        $activityItems = $digestItems->where('notification_class', InstitutionActivityNotification::class);

        if ($activityItems->isEmpty()) {
            return $digestItems;
        }

        $requestIds = $activityItems->flatMap(fn ($item) => $item->data['activity_request_ids'] ?? [])->unique();

        if ($requestIds->isEmpty()) {
            $activityRequests = new Collection;
        } else {
            $activityRequests = InstitutionActivityRequest::query()->open()->whereKey($requestIds)
                ->where('recipient_id', $user->id)->whereHas('institution')->whereHas('recipient')
                ->with(['institution.meetings', 'institution.checkIns', 'institution.types', 'recipient', 'requestedBy', 'task'])->get()->keyBy('id');
            $activityRequests->where('campaign_type', InstitutionActivityCampaign::MissingMeetings)
                ->loadMissing(['institution.meetings.agendaItems.votes', 'institution.meetings.institutions.types']);
        }

        return $digestItems->filter(function ($item) use ($user, $activityRequests, $eligibility): bool {
            if ($item->notification_class !== InstitutionActivityNotification::class || ! isset($item->data['activity_request_ids'])) {
                return true;
            }
            $requests = $activityRequests->only($item->data['activity_request_ids'])->values()->filter($eligibility->shouldDeliver(...));
            if ($requests->isEmpty() || $user->isGloballyMuted() || $user->emailDeliveryFor(NotificationType::InstitutionActivity) !== EmailDelivery::Digest) {
                $item->delete();

                return false;
            }
            $item->data = new InstitutionActivityNotification(new Collection($requests->all()))->toDigestItem($user);

            return true;
        });
    }

    /**
     * Daily delivery follows the local clock, including across DST changes.
     *
     * @param  SupportCollection<int, NotificationDigestQueue>|null  $items
     */
    protected function shouldSendDigest(User $user, ?SupportCollection $items = null): bool
    {
        $frequencyHours = $user->getDigestFrequencyHours();

        $oldestItem = $items !== null
            ? $items->first()
            : NotificationDigestQueue::where('user_id', $user->id)
                ->orderBy('created_at', 'asc')
                ->first();

        if (! $oldestItem) {
            return false;
        }

        if ($frequencyHours === 24) {
            return now()->setTimezone(config('app.timezone'))->hour === $user->getDigestSendHour();
        }

        $oldestTime = Carbon::parse($oldestItem->created_at);
        $hoursSinceOldest = $oldestTime->diffInHours(now());

        return $hoursSinceOldest >= $frequencyHours;
    }
}
