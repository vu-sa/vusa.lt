<?php

namespace App\Jobs;

use App\Models\Meeting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rewrites stored meeting titles after an institution type's meeting kind changed, and reindexes
 * the ones that did change. Queued: a type can carry hundreds of institutions.
 */
class RefreshMeetingTitles implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<string>  $institutionIds
     */
    public function __construct(public array $institutionIds)
    {
        $this->afterCommit();
    }

    public function handle(): void
    {
        Meeting::query()
            ->withTrashed()
            ->whereHas('institutions', fn (Builder $institutions) => $institutions->whereIn('institutions.id', $this->institutionIds))
            ->with('institutions.types')
            ->chunkById(200, function (Collection $meetings): void {
                $meetings
                    ->filter(fn (Meeting $meeting) => $meeting->refreshTitle())
                    ->each(fn (Meeting $meeting) => SyncMeetingOutcomeIndex::dispatch($meeting->getKey()));
            });
    }
}
