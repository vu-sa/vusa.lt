<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Goals\LinkAgendaItemGoalRequest;
use App\Models\Goal;
use App\Models\Pivots\AgendaItem;
use Illuminate\Http\RedirectResponse;

/**
 * Goals pilot: an agenda item counts towards a goal as a step on the goal, dated by its meeting,
 * so the goal's history fills itself from meetings.
 */
class AgendaItemGoalController extends AdminController
{
    public function store(LinkAgendaItemGoalRequest $request, AgendaItem $agendaItem): RedirectResponse
    {
        $goal = Goal::query()->findOrFail($request->validated('goal_id'));
        $agendaItem->loadMissing('meeting');

        $goal->steps()->firstOrCreate(['agenda_item_id' => $agendaItem->id], [
            'title' => $agendaItem->getTranslations('title') ?: ['lt' => (string) $agendaItem->title],
            'happened_on' => ($agendaItem->meeting->start_time ?? now())->toDateString(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', __('goals.messages.agenda_item_linked'));
    }

    public function destroy(AgendaItem $agendaItem, Goal $goal): RedirectResponse
    {
        $this->handleAuthorization('update', $goal);

        $goal->steps()->where('agenda_item_id', $agendaItem->id)->get()->each->delete();

        return back()->with('success', __('goals.messages.agenda_item_unlinked'));
    }
}
