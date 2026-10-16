<?php

namespace App\Actions;

use App\Enums\MailingListMode;
use App\Enums\MailingListSyncStatus;
use App\Models\MailingList;
use App\Models\User;
use App\Services\Mailman\MailmanClient;
use App\ValueObjects\MailingListDiff;
use App\ValueObjects\MailingListRecipient;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/** Manual lists are read-only; managed lists follow the resolved sources. */
class SyncMailingList
{
    public function __construct(private readonly MailmanClient $mailman) {}

    public function preview(MailingList $mailingList): MailingListDiff
    {
        $current = collect($this->mailman->roster($this->listId($mailingList)))->keyBy('email');
        $desired = ResolveMailingListMembers::execute($mailingList)->keyBy('email');

        return new MailingListDiff(
            toAdd: $desired->diffKeys($current)->values()->all(),
            toRemove: $current->diffKeys($desired)->values()->all(),
            desired: $desired->values()->all(),
            currentCount: $current->count(),
        );
    }

    public function reviewToken(MailingList $list, MailingListDiff $diff, User $user): string
    {
        return Crypt::encryptString(json_encode([
            'list' => $list->id,
            'user' => $user->id,
            'expires' => now()->addMinutes(15)->timestamp,
            'fingerprint' => $this->fingerprint($list, $diff),
        ], JSON_THROW_ON_ERROR));
    }

    public function apply(MailingList $mailingList, ?string $previewToken = null, ?User $causer = null, bool $activate = false): MailingListDiff
    {
        return $mailingList->withSyncLock(function () use ($mailingList, $previewToken, $causer, $activate): MailingListDiff {
            $mailingList->refresh();

            return $this->applyLocked($mailingList, $previewToken, $causer, $activate);
        });
    }

    private function applyLocked(MailingList $mailingList, ?string $previewToken, ?User $causer, bool $activate): MailingListDiff
    {
        if (! $mailingList->isManaged() && ! $activate) {
            $this->refreshSnapshot($mailingList);

            return new MailingListDiff([], [], [], $mailingList->member_count);
        }

        try {
            $diff = $this->preview($mailingList);

            if ($activate || $previewToken !== null) {
                $this->validateReview($mailingList, $diff, $previewToken, $causer);
            }

            if ($diff->needsReview() && $previewToken === null) {
                $mailingList->update([
                    'last_sync_status' => MailingListSyncStatus::NeedsReview,
                    'last_sync_error' => null,
                    'last_synced_at' => now(),
                ]);

                return $diff;
            }

            if (! $diff->isEmpty()) {
                $this->write($mailingList, $diff);
                $this->log($mailingList, $diff, $causer);
            }

            $this->storeSnapshot($mailingList, $diff->desired);
            $mailingList->update([
                'mode' => MailingListMode::Managed,
                'member_count' => count($diff->desired),
                'last_sync_status' => MailingListSyncStatus::Synced,
                'last_sync_error' => null,
                'last_synced_at' => now(),
            ]);

            return $diff;
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            try {
                $this->refreshSnapshot($mailingList);
            } catch (Throwable $snapshotException) {
                report($snapshotException);
            }
            $mailingList->update([
                'last_sync_status' => MailingListSyncStatus::Failed,
                'last_sync_error' => mb_substr($exception->getMessage(), 0, 1000),
                'last_synced_at' => now(),
            ]);

            throw $exception;
        }
    }

    public function refreshUnmanaged(MailingList $mailingList): void
    {
        $mailingList->withSyncLock(function () use ($mailingList): void {
            $mailingList->refresh();
            if (! $mailingList->isManaged()) {
                $this->refreshSnapshot($mailingList);
            }
        });
    }

    private function refreshSnapshot(MailingList $mailingList): void
    {
        $roster = $this->mailman->roster($this->listId($mailingList));

        $this->storeSnapshot($mailingList, array_map(
            fn (array $member) => new MailingListRecipient($member['email'], $member['display_name'], null, []),
            $roster,
        ), withReasons: false);

        $mailingList->update(['member_count' => count($roster)]);
    }

    private function fingerprint(MailingList $list, MailingListDiff $diff): string
    {
        return hash('sha256', json_encode([
            $list->tenant_id, $list->mode->value, $diff->toArray(),
            array_column($diff->toRemove, 'member_id'),
        ], JSON_THROW_ON_ERROR));
    }

    private function validateReview(MailingList $list, MailingListDiff $diff, ?string $token, ?User $user): void
    {
        try {
            $review = json_decode(Crypt::decryptString($token ?? ''), true, flags: JSON_THROW_ON_ERROR);
            $valid = is_array($review) && $user !== null
                && ($review['list'] ?? null) === $list->id
                && ($review['user'] ?? null) === $user->id
                && ($review['expires'] ?? 0) > now()->timestamp
                && hash_equals($this->fingerprint($list, $diff), (string) ($review['fingerprint'] ?? ''));
        } catch (Throwable) {
            $valid = false;
        }

        if (! $valid) {
            throw ValidationException::withMessages(['preview_token' => __('mailing_lists.messages.stale_preview')]);
        }
    }

    private function write(MailingList $mailingList, MailingListDiff $diff): void
    {
        $listId = $this->listId($mailingList);

        // Leave time to restore delivery settings and refresh the snapshot before the worker timeout.
        $deadline = microtime(true) + 480;
        $this->mailman->silently($listId, function () use ($listId, $diff, $deadline): void {
            foreach ($diff->toRemove as $member) {
                $this->ensureWithinDeadline($deadline);
                $this->mailman->unsubscribe($member['member_id']);
            }

            foreach ($diff->toAdd as $recipient) {
                $this->ensureWithinDeadline($deadline);
                $this->mailman->subscribe($listId, $recipient->email, $recipient->displayName);
            }
        });
    }

    private function ensureWithinDeadline(float $deadline): void
    {
        if (microtime(true) >= $deadline) {
            throw new \RuntimeException('Mailing list synchronization exceeded its time budget.');
        }
    }

    /**
     * @param  list<MailingListRecipient>  $recipients
     */
    private function storeSnapshot(MailingList $mailingList, array $recipients, bool $withReasons = true): void
    {
        DB::transaction(function () use ($mailingList, $recipients, $withReasons): void {
            $mailingList->members()->delete();

            $now = now();
            $rows = array_map(fn (MailingListRecipient $recipient) => [
                'mailing_list_id' => $mailingList->id,
                'email' => $recipient->email,
                'display_name' => $recipient->displayName,
                'user_id' => $recipient->userId,
                'reasons' => $withReasons ? json_encode($recipient->reasons) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $recipients);

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('mailing_list_members')->insert($chunk);
            }
        });
    }

    private function log(MailingList $mailingList, MailingListDiff $diff, ?User $causer): void
    {
        $activity = activity()
            ->performedOn($mailingList)
            ->event('members_synced')
            ->withProperties([
                'added' => array_map(fn (MailingListRecipient $r) => $r->email, $diff->toAdd),
                'removed' => array_column($diff->toRemove, 'email'),
            ]);

        if ($causer !== null) {
            $activity->causedBy($causer);
        }

        $activity->log('members_synced');
    }

    private function listId(MailingList $mailingList): string
    {
        return $mailingList->mailman_list_id ?? MailingList::listIdFor($mailingList->address);
    }
}
