<?php

namespace App\Listeners;

use App\Events\SearchRelationChanged;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use Illuminate\Events\Dispatcher;

/**
 * Re-indexes the documents that embed a relation when it changes: meetings and agenda items
 * carry their institutions' type ids (the facts public visibility is decided from), so a type
 * or institution change must reach them — as it must the public meeting index.
 */
class SyncRelationSearchIndex
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(SearchRelationChanged::class, fn (SearchRelationChanged $event) => $this->relationChanged($event));
    }

    private function relationChanged(SearchRelationChanged $event): void
    {
        match (true) {
            $event->model instanceof Institution && $event->relation === 'types' => $this->institutionTypesChanged($event->model),
            $event->model instanceof Meeting && $event->relation === 'institutions' => $this->meetingInstitutionsChanged($event->model),
            default => null,
        };
    }

    private function institutionTypesChanged(Institution $institution): void
    {
        $institution->unsetRelation('types')->searchable();

        $meetings = Meeting::query()->whereHas('institutions', fn ($query) => $query->where('institutions.id', $institution->getKey()));

        (clone $meetings)->chunkById(100, function ($chunk): void {
            $chunk->searchable();
            $chunk->each(fn (Meeting $meeting) => $meeting->syncPublicSearchIndex());
        });
        AgendaItem::query()
            ->whereIn('meeting_id', (clone $meetings)->select('meetings.id'))
            ->chunkById(200, fn ($chunk) => $chunk->searchable());
    }

    private function meetingInstitutionsChanged(Meeting $meeting): void
    {
        $meeting->unsetRelation('institutions')->searchable();
        $meeting->agendaItems()->get()->searchable();
        $meeting->syncPublicSearchIndex();
    }
}
