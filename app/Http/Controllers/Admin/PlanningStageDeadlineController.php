<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\PlanningStageDeadline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class PlanningStageDeadlineController extends AdminController
{
    /**
     * Show the form for editing deadlines for an academic year.
     */
    public function edit(int $academicYear): Response
    {
        $this->authorize('update', \App\Models\PlanningProcess::class);

        $deadlines = PlanningStageDeadline::where('academic_year_start', $academicYear)
            ->orderBy('stage')
            ->get();

        return $this->inertiaResponse('Admin/PlanningProcesses/EditPlanningDeadlines', [
            'academicYear' => $academicYear,
            'deadlines' => $deadlines->map->toArray(),
        ]);
    }

    /**
     * Update deadlines for an academic year.
     */
    public function update(Request $request, int $academicYear): RedirectResponse
    {
        $this->authorize('update', \App\Models\PlanningProcess::class);

        $validated = $request->validate([
            'deadlines' => ['required', 'array'],
            'deadlines.*.stage' => ['required', 'integer', 'min:1', 'max:5'],
            'deadlines.*.starts_at' => ['required', 'date'],
            'deadlines.*.ends_at' => ['required', 'date', 'after_or_equal:deadlines.*.starts_at'],
        ]);

        foreach ($validated['deadlines'] as $deadline) {
            PlanningStageDeadline::updateOrCreate(
                ['academic_year_start' => $academicYear, 'stage' => $deadline['stage']],
                ['starts_at' => $deadline['starts_at'], 'ends_at' => $deadline['ends_at']]
            );
        }

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => __('planning.deadlines')]));
    }
}
