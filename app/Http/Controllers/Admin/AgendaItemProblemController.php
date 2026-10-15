<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreAgendaItemProblemRequest;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use Illuminate\Http\RedirectResponse;

/**
 * Links a problem to the agenda item where representatives raised it. The link is edited on the
 * agenda item record, not in the problem form (relations live on the record).
 */
class AgendaItemProblemController extends AdminController
{
    public function store(StoreAgendaItemProblemRequest $request, AgendaItem $agendaItem): RedirectResponse
    {
        $problem = Problem::query()->findOrFail($request->validated('problem_id'));

        $agendaItem->problems()->syncWithoutDetaching([$problem->id]);

        // The meeting's institutions become the problem's too, within its padalinys (ProblemRequest's rule).
        $problem->institutions()->syncWithoutDetaching(
            $agendaItem->meeting->institutions()
                ->where('institutions.tenant_id', $problem->tenant_id)
                ->pluck('institutions.id')
                ->all()
        );

        return back()->with('success', __('problems.linked'));
    }

    public function destroy(AgendaItem $agendaItem, Problem $problem): RedirectResponse
    {
        $this->handleAuthorization('update', $agendaItem);

        abort_unless($agendaItem->problems()->whereKey($problem->id)->exists(), 404);

        $agendaItem->problems()->detach($problem->id);

        return back()->with('success', __('problems.unlinked'));
    }
}
