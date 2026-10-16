<?php

namespace App\Jobs;

use App\Actions\SyncMailingList;
use App\Enums\MailingListMode;
use App\Models\MailingList;
use App\Models\User;
use App\Services\Mailman\MailmanClient;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Resyncs every managed list. Unique while queued, so a burst of duty edits (an end-of-term
 * handover touches dozens of assignments) collapses into one run.
 */
class SyncManagedMailingLists implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public $tries = 1;

    public $timeout = 600;

    public int $uniqueFor = 900;

    public function __construct(
        public ?string $mailingListId = null,
        public ?string $previewToken = null,
        public ?string $causerId = null,
        public bool $activate = false,
    ) {
        $this->onConnection('long-running');
    }

    public function handle(MailmanClient $mailman, SyncMailingList $sync): void
    {
        if (! $mailman->isConfigured()) {
            return;
        }

        if ($this->mailingListId === null) {
            MailingList::query()->where('mode', MailingListMode::Managed)
                ->each(fn (MailingList $list) => self::dispatch($list->id));

            return;
        }

        $list = MailingList::query()
            ->when(! $this->activate, fn ($query) => $query->where('mode', MailingListMode::Managed))
            ->find($this->mailingListId);
        if ($list === null) {
            return;
        }
        try {
            $causer = $this->causerId !== null ? User::query()->find($this->causerId) : null;
            $sync->apply($list, $this->previewToken, $causer, $this->activate);
        } catch (LockTimeoutException) {
            self::dispatch($list->id, $this->previewToken, $this->causerId, $this->activate)->delay(now()->addMinute());
        } catch (Throwable $exception) {
            Log::warning('Mailing list sync failed', ['list' => $list->address, 'error' => $exception->getMessage()]);
        }
    }

    public function uniqueId(): string
    {
        if ($this->mailingListId === null) {
            return 'all';
        }

        return $this->mailingListId.($this->activate ? ':activate' : '').($this->previewToken !== null ? ':review' : '');
    }
}
