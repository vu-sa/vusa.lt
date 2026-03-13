<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\PlanningActivity;
use App\Models\PlanningProcess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanningActivityController extends AdminController
{
    /**
     * Store a newly created activity.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'planning_process_id' => ['required', 'string', 'exists:planning_processes,id'],
            'name' => ['required', 'string', 'max:255'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'responsible_person' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:padalinio,strateginis,srities'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $planningProcess = PlanningProcess::findOrFail($validated['planning_process_id']);
        $this->handleAuthorization('update', $planningProcess);

        PlanningActivity::create($validated);

        return back()->with('success', trans_choice('messages.created', 0, ['model' => __('planning.activity')]));
    }

    /**
     * Update the specified activity.
     */
    public function update(Request $request, PlanningActivity $planningActivity): RedirectResponse
    {
        $this->handleAuthorization('update', $planningActivity->planningProcess);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'responsible_person' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'string', 'in:padalinio,strateginis,srities'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $planningActivity->update($validated);

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => __('planning.activity')]));
    }

    /**
     * Remove the specified activity.
     */
    public function destroy(PlanningActivity $planningActivity): RedirectResponse
    {
        $this->handleAuthorization('update', $planningActivity->planningProcess);

        $planningActivity->delete();

        return back()->with('success', trans_choice('messages.deleted', 0, ['model' => __('planning.activity')]));
    }
}
