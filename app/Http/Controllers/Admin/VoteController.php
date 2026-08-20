<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreVoteRequest;
use App\Http\Requests\UpdateVoteRequest;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;

class VoteController extends AdminController
{
    /**
     * Store a newly created vote.
     */
    public function store(StoreVoteRequest $request)
    {
        $agendaItem = AgendaItem::findOrFail($request->agenda_item_id);

        $this->handleAuthorization('update', $agendaItem);

        // Check if this is the first vote (make it main)
        $isFirstVote = ! $agendaItem->votes()->exists();

        $vote = Vote::create([
            'agenda_item_id' => $request->agenda_item_id,
            'is_main' => $request->has('is_main') ? $request->is_main : $isFirstVote,
            'title' => $request->title,
            'student_vote' => $request->student_vote,
            'decision' => $request->decision,
            'student_benefit' => $request->student_benefit,
            'note' => $request->note,
        ]);

        // If this vote is marked as main, unset main flag from other votes
        if ($vote->is_main) {
            $agendaItem->votes()
                ->where('id', '!=', $vote->id)
                ->update(['is_main' => false]);
        }

        return back()->with('success', $this->entityMessage('created', 'vote'));
    }

    /**
     * Update the specified vote.
     */
    public function update(UpdateVoteRequest $request, Vote $vote)
    {
        $agendaItem = $vote->agendaItem;

        $this->handleAuthorization('update', $agendaItem);

        $vote->fill($request->validated());
        $vote->save();

        // If this vote is now marked as main, unset main flag from other votes
        if ($request->has('is_main') && $request->is_main) {
            $agendaItem->votes()
                ->where('id', '!=', $vote->id)
                ->update(['is_main' => false]);
        }

        return back()->with('success', $this->entityMessage('updated', 'vote'));
    }

    /**
     * Remove the specified vote.
     */
    public function destroy(Vote $vote)
    {
        $agendaItem = $vote->agendaItem;

        $this->handleAuthorization('update', $agendaItem);

        $wasMain = $vote->is_main;

        $vote->delete();

        // If we deleted the main vote, make the first remaining vote the main one
        if ($wasMain) {
            $firstVote = $agendaItem->votes()->orderBy('order')->first();
            if ($firstVote) {
                $firstVote->update(['is_main' => true]);
            }
        }

        return back()->with('success', $this->entityMessage('deleted', 'vote'));
    }

    /**
     * Set a vote as the main vote for an agenda item.
     */
    public function setMain(Vote $vote)
    {
        $agendaItem = $vote->agendaItem;

        $this->handleAuthorization('update', $agendaItem);

        // Unset main flag from all other votes
        $agendaItem->votes()
            ->where('id', '!=', $vote->id)
            ->update(['is_main' => false]);

        // Set this vote as main
        $vote->update(['is_main' => true]);

        return back()->with('success', __('messages.vote.main_changed'));
    }
}
