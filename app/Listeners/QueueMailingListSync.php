<?php

namespace App\Listeners;

use App\Enums\MailingListMode;
use App\Events\DutiableChanged;
use App\Events\SearchRelationChanged;
use App\Jobs\SyncManagedMailingLists;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\MailingList;
use App\Models\User;
use App\Services\Mailman\MailmanClient;
use Illuminate\Database\Eloquent\Model;

class QueueMailingListSync
{
    /** Gives an editor time to finish a handover before the lists follow. */
    private const int DELAY_MINUTES = 5;

    public function handle(DutiableChanged|SearchRelationChanged $event): void
    {
        if ($event instanceof SearchRelationChanged && ! ($event->model instanceof Duty || $event->model instanceof Institution)) {
            return;
        }
        self::queueSync();
    }

    public function updated(Model $model): void
    {
        $fields = match (true) {
            $model instanceof Duty => ['email', 'name', 'is_active', 'institution_id'],
            $model instanceof Institution => ['is_active', 'tenant_id'],
            $model instanceof User => ['email', 'name'],
            $model instanceof DutyType, $model instanceof InstitutionType => ['parent_id'],
            default => [],
        };
        if ($model->wasChanged($fields)) {
            self::queueSync();
        }
    }

    public function deleted(Model $model): void
    {
        self::queueSync();
    }

    public function restored(Model $model): void
    {
        self::queueSync();
    }

    public static function queueSync(): void
    {
        if (! app(MailmanClient::class)->isConfigured()
            || ! MailingList::query()->where('mode', MailingListMode::Managed)->exists()) {
            return;
        }

        SyncManagedMailingLists::dispatch()->delay(now()->addMinutes(self::DELAY_MINUTES))->afterCommit();
    }
}
