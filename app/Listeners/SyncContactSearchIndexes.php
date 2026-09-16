<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\User;
use App\Services\ContactSearchIndexSynchronizer;
use App\Support\MorphMap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncContactSearchIndexes implements ShouldQueue
{
    use InteractsWithQueue;

    public $afterCommit = true;

    public function handle(DutiableChanged $event): void
    {
        if ($event->dutiableType !== MorphMap::alias(User::class)) {
            return;
        }

        $duty = Duty::query()->find($event->dutyId);

        if ($duty === null) {
            return;
        }

        app(ContactSearchIndexSynchronizer::class)->assignmentChanged(
            $duty,
            User::query()->find($event->modelId),
        );
    }
}
