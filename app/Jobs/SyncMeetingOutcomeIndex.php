<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\PublicMeeting;
use App\Services\Typesense\MeetingSearchLock;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Refreshes every document built from a meeting's votes. Unique while queued, so saving a whole
 * agenda item with its votes collapses into one run, which reads the committed result.
 */
class SyncMeetingOutcomeIndex implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(public string $meetingId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->meetingId;
    }

    /** The lock keeps this write from landing after AgendaItem::save() evicts a newly private item. */
    public function handle(MeetingSearchLock $lock): void
    {
        $lock->run($this->meetingId, function (): void {
            $meeting = Meeting::query()->find($this->meetingId);

            if ($meeting === null) {
                return;
            }

            $meeting->searchableUsing()->update(new Collection([$meeting]));

            $items = $meeting->agendaItems()->get();
            if ($items->isNotEmpty()) {
                $items->first()->searchableUsing()->update($items);
            }

            $publicMeeting = PublicMeeting::query()->find($meeting->id);
            if ($publicMeeting?->shouldBeSearchable()) {
                $publicMeeting->searchableUsing()->update(new Collection([$publicMeeting]));
            }
        });
    }
}
