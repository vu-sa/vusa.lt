<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\PlanningMonitoringEntry;
use App\Models\PlanningProcess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanningMonitoringController extends AdminController
{
    /**
     * Store a new monitoring entry (or update existing for same quarter).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'planning_process_id' => ['required', 'string', 'exists:planning_processes,id'],
            'quarter' => ['required', 'integer', 'min:1', 'max:4'],
            'status_text' => ['required', 'string'],
        ]);

        $planningProcess = PlanningProcess::findOrFail($validated['planning_process_id']);
        $this->handleAuthorization('update', $planningProcess);

        PlanningMonitoringEntry::updateOrCreate(
            [
                'planning_process_id' => $validated['planning_process_id'],
                'quarter' => $validated['quarter'],
            ],
            [
                'status_text' => $validated['status_text'],
                'submitted_by' => auth()->id(),
            ]
        );

        return back()->with('success', trans_choice('messages.created', 0, ['model' => __('planning.monitoring_entry')]));
    }

    /**
     * Update the specified monitoring entry.
     */
    public function update(Request $request, PlanningMonitoringEntry $planningMonitoringEntry): RedirectResponse
    {
        $this->handleAuthorization('update', $planningMonitoringEntry->planningProcess);

        $validated = $request->validate([
            'status_text' => ['required', 'string'],
        ]);

        $planningMonitoringEntry->update([
            'status_text' => $validated['status_text'],
            'submitted_by' => auth()->id(),
        ]);

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => __('planning.monitoring_entry')]));
    }
}
