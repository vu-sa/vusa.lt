<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreAgendaItemsRequest;
use App\Http\Requests\UpdateAgendaItemRequest;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgendaItemController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

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
                    'title' => $agendaItemTitle,
                    'order' => $maxOrder + $index + 1,
                    'brought_by_students' => $broughtByStudentsFlags[$index] ?? false,
                ]);
            }

            // We no longer create tasks for placeholder agenda items
        }

        return back()->with(['success' => 'Darbotvarkės punktai sukurti sėkmingai!']);
    }

    /**
     * Display the specified resource.
     */
    public function show(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('view', $agendaItem);

        return $this->inertiaResponse('Admin/Representation/ShowAgendaItem', [
            'agendaItem' => $agendaItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAgendaItemRequest $request, AgendaItem $agendaItem)
    {
        DB::transaction(function () use ($request, $agendaItem) {
            // Update agenda item fields (excluding votes)
            $agendaItem->fill($request->safe()->except('votes'));
            $agendaItem->save();

            // Handle votes if provided
            if ($request->has('votes')) {
                $this->syncVotes($agendaItem, $request->input('votes'));
            }
        });

        return back()->with('success', 'Darbotvarkės punktas atnaujintas sėkmingai!');
    }

    /**
     * Sync votes for an agenda item.
     */
    protected function syncVotes(AgendaItem $agendaItem, array $votes): void
    {
        $existingVoteIds = $agendaItem->votes()->pluck('id')->toArray();
        $updatedVoteIds = [];

        foreach ($votes as $voteData) {
            if (isset($voteData['id']) && $voteData['id'] !== null) {
                // Update existing vote
                $vote = Vote::find($voteData['id']);
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
                    $updatedVoteIds[] = $vote->id;
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
                $updatedVoteIds[] = $vote->id;
            }
        }

        // Delete votes that were removed
        $votesToDelete = array_diff($existingVoteIds, $updatedVoteIds);
        if (! empty($votesToDelete)) {
            Vote::whereIn('id', $votesToDelete)->delete();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AgendaItem $agendaItem)
    {
        $this->handleAuthorization('delete', $agendaItem);

        $agendaItem->delete();

        return back()->with(['success' => 'Darbotvarkės punktas ištrintas sėkmingai!']);
    }

    /**
     * Reorder agenda items for a meeting.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'meeting_id' => 'required|exists:meetings,id',
            'agenda_items' => 'required|array',
            'agenda_items.*.id' => 'required|exists:agenda_items,id',
            'agenda_items.*.order' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->agenda_items as $item) {
                AgendaItem::where('id', $item['id'])
                    ->where('meeting_id', $request->meeting_id)
                    ->update(['order' => $item['order']]);
            }
        });

        return back()->with(['success' => 'Darbotvarkės punktų tvarka pakeista sėkmingai!']);
    }
}
