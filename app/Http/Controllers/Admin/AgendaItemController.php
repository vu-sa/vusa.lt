<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\ReorderAgendaItemsRequest;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Http\Requests\UpdateAgendaItemRequest;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AgendaItemController extends AdminController
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAgendaItemsRequest $request)
    {
        if ($request->has('agendaItemTitles')) {
            $validatedData = $request->safe();

            // Get the highest order for this meeting
            $maxOrder = AgendaItem::where('meeting_id', $validatedData['meeting_id'])
                ->max('order') ?? 0;

            // Get broughtByStudentsFlags array (defaults to empty array)
            $broughtByStudentsFlags = $request->input('broughtByStudentsFlags', []);

            foreach ($validatedData['agendaItemTitles'] as $index => $agendaItemTitle) {
                AgendaItem::create([
                    'meeting_id' => $validatedData['meeting_id'],
                    // Pinned to `lt`, not the request locale: the paste box is always fed
                    // Lithuanian, including by an admin whose interface is in English.
                    'title' => ['lt' => $agendaItemTitle],
                    'order' => $maxOrder + $index + 1,
                    'brought_by_students' => $broughtByStudentsFlags[$index] ?? false,
                ]);
            }

            // We no longer create tasks for placeholder agenda items
        }

        return back()->with(['success' => __('messages.agenda_item.created_many')]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('view', $agendaItem);

        $agendaItem->load(['votes', 'meeting.institutions']);

        return $this->inertiaResponse('Admin/Representation/ShowAgendaItem', [
            'agendaItem' => $agendaItem,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AgendaItem $agendaItem)
    {
        // This page doubles as the read-only "show" surface (it hosts the notes
        // and discussion). Gate it on the broad `view` ability so coordinators
        // and related-institution viewers can open it; editing affordances are
        // gated client-side by the `canUpdate` prop, and the update/store
        // endpoints remain `update`-gated.
        $this->handleAuthorization('view', $agendaItem);

        $canUpdate = Gate::allows('update', $agendaItem);

        $agendaItem->load(['votes', 'note', 'meeting.institutions.types', 'meeting.agendaItems' => function ($query): void {
            $query->orderBy('order')->with('mainVote')->withCount('comments')
                ->withExists(['note as has_notes' => fn ($note) => $note->whereNotNull('notes_html')]);
        }]);

        // Whether votes/description are publicly visible follows the meeting's
        // institution settings (computed attribute, not auto-appended).
        $agendaItem->meeting->append('is_public');

        // Lightweight sibling list for in-meeting navigation (popover + prev/next)
        $siblingAgendaItems = $agendaItem->meeting->agendaItems
            ->map(fn (AgendaItem $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type?->value,
                'order' => $item->order,
                'brought_by_students' => (bool) $item->brought_by_students,
                'main_vote' => $item->mainVote,
                'comments_count' => $item->comments_count,
                'has_notes' => (bool) $item->getAttribute('has_notes'),
                // Lets the editor default this item's start time from the previous item's end
                // time — see EditAgendaItem.vue.
                'start_time' => $item->start_time,
                'end_time' => $item->end_time,
            ])
            ->values();

        return $this->inertiaResponse('Admin/Representation/EditAgendaItem', [
            // toFullArray(), not the model: the editor writes translations, so it needs the
            // whole `{lt, en}` map rather than the current locale's string.
            'agendaItem' => [
                ...$agendaItem->toFullArray(),
                'votes' => $agendaItem->votes->map->toFullArray()->all(),
            ],
            'siblingAgendaItems' => $siblingAgendaItems,
            'canUpdate' => $canUpdate,
            // VU SA's own bodies have no separate student position to record — see
            // Meeting::requiresStudentPerspective().
            'requiresStudentPerspective' => $agendaItem->meeting->requiresStudentPerspective(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgendaItemRequest $request, AgendaItem $agendaItem)
    {
        DB::transaction(function () use ($request, $agendaItem): void {
            // Update agenda item fields (excluding votes)
            $agendaItem->fill($request->safe()->except('votes'));
            $agendaItem->save();

            // Handle votes if provided
            if ($request->has('votes')) {
                // validated(), not input(): raw input would carry through anything unvalidated.
                $this->syncVotes($agendaItem, $request->validated('votes') ?? []);
            }
        });

        return back()->with('success', $this->entityMessage('updated', 'agendaItem'));
    }

    /**
     * Sync votes for an agenda item.
     */
    protected function syncVotes(AgendaItem $agendaItem, array $votes): void
    {
        $existingVoteIds = $agendaItem->votes()->pluck('id')->toArray();
        $updatedVoteIds = [];

        foreach ($votes as $voteData) {
            $voteId = $voteData['id'] ?? null;
            if ($voteId !== null) {
                // Update existing vote
                $vote = Vote::find($voteId);
                if ($vote && $vote->agenda_item_id === $agendaItem->id) {
                    $vote->update([
                        'is_main' => $voteData['is_main'] ?? false,
                        'is_consensus' => $voteData['is_consensus'] ?? false,
                        'title' => $voteData['title'] ?? null,
                        'student_vote' => $voteData['student_vote'] ?? null,
                        'decision' => $voteData['decision'] ?? null,
                        'student_benefit' => $voteData['student_benefit'] ?? null,
                        'note' => $voteData['note'] ?? null,
                        'order' => $voteData['order'] ?? 0,
                    ]);
                    $updatedVoteIds[] = (string) $vote->getKey();
                }
            } else {
                // Create new vote
                $vote = $agendaItem->votes()->create([
                    'is_main' => $voteData['is_main'] ?? false,
                    'is_consensus' => $voteData['is_consensus'] ?? false,
                    'title' => $voteData['title'] ?? null,
                    'student_vote' => $voteData['student_vote'] ?? null,
                    'decision' => $voteData['decision'] ?? null,
                    'student_benefit' => $voteData['student_benefit'] ?? null,
                    'note' => $voteData['note'] ?? null,
                    'order' => $voteData['order'] ?? 0,
                ]);
                $updatedVoteIds[] = (string) $vote->getKey();
            }
        }

        // Delete votes that were removed
        $votesToDelete = array_diff($existingVoteIds, $updatedVoteIds);
        if (! empty($votesToDelete)) {
            Vote::whereIn('id', $votesToDelete)->delete();
        }

        $this->ensureSingleMainVote($agendaItem);
    }

    /**
     * Keep "exactly one main vote, or none at all" true whatever the payload said.
     *
     * The editor may delete the main vote — an item is allowed to end up with no votes — but a
     * payload that removes it without promoting a survivor would otherwise leave the remaining
     * votes with no main one, and every reader (mainVote(), completion, the public page) treats
     * that as missing data.
     */
    protected function ensureSingleMainVote(AgendaItem $agendaItem): void
    {
        /** @var Collection<int, Vote> $votes */
        $votes = $agendaItem->votes()->orderBy('order')->get();

        if ($votes->isEmpty() || $votes->where('is_main', true)->count() === 1) {
            return;
        }

        $main = $votes->firstWhere('is_main', true) ?? $votes->first();

        foreach ($votes as $vote) {
            $shouldBeMain = $vote->getKey() === $main->getKey();

            if ((bool) $vote->is_main !== $shouldBeMain) {
                $vote->update(['is_main' => $shouldBeMain]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('delete', $agendaItem);

        $agendaItem->delete();

        return back()->with(['success' => $this->entityMessage('deleted', 'agendaItem')]);
    }

    /**
     * Reorder agenda items for a meeting.
     */
    public function reorder(ReorderAgendaItemsRequest $request)
    {
        $validated = $request->validated();

        // Reordering is a write against the meeting's agenda, so it follows the meeting's
        // update ability. The scoped where() below already confines the writes to this
        // meeting's items, so authorizing the meeting covers every row touched.
        $this->handleAuthorization('update', Meeting::query()->findOrFail($validated['meeting_id']));

        DB::transaction(function () use ($request): void {
            foreach ($request->agenda_items as $item) {
                AgendaItem::where('id', $item['id'])
                    ->where('meeting_id', $request->meeting_id)
                    ->update(['order' => $item['order']]);
            }
        });

        return back()->with(['success' => __('messages.agenda_item.reordered')]);
    }
}
