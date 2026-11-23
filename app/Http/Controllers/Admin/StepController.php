<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\Goals\StepRequest;
use App\Models\Goal;
use App\Models\Problem;
use App\Models\Step;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\DB;

/**
 * Steps of the goals pilot, written from a goal's or a problem's page. The routes use scoped
 * bindings, so `{step}` only resolves through the parent in the URL — hence a method pair per parent.
 */
class StepController extends AdminController
{
    #[Authorize('update', 'goal')]
    public function storeForGoal(StepRequest $request, Goal $goal): RedirectResponse
    {
        return $this->store($request, $goal);
    }

    #[Authorize('update', 'goal')]
    public function updateForGoal(StepRequest $request, Goal $goal, Step $step): RedirectResponse
    {
        return $this->update($request, $step);
    }

    #[Authorize('update', 'goal')]
    public function destroyForGoal(Goal $goal, Step $step): RedirectResponse
    {
        return $this->destroy($step);
    }

    #[Authorize('update', 'problem')]
    public function storeForProblem(StepRequest $request, Problem $problem): RedirectResponse
    {
        return $this->store($request, $problem);
    }

    #[Authorize('update', 'problem')]
    public function updateForProblem(StepRequest $request, Problem $problem, Step $step): RedirectResponse
    {
        return $this->update($request, $step);
    }

    #[Authorize('update', 'problem')]
    public function destroyForProblem(Problem $problem, Step $step): RedirectResponse
    {
        return $this->destroy($step);
    }

    private function store(StepRequest $request, Goal|Problem $parent): RedirectResponse
    {
        DB::transaction(function () use ($parent, $request): void {
            $step = $parent->steps()->create([
                ...$request->safe()->except('performers'),
                'created_by' => $request->user()->id,
            ]);

            // Whoever records a step usually did it; an explicit empty list means nobody in particular.
            $step->performers()->sync($request->has('performers') ? $request->validated('performers') ?? [] : [$request->user()->id]);
        });

        return back()->with('success', __('goals.messages.step_saved'));
    }

    private function update(StepRequest $request, Step $step): RedirectResponse
    {
        DB::transaction(function () use ($step, $request): void {
            $step->update($request->safe()->except('performers'));

            if ($request->has('performers')) {
                $step->performers()->sync($request->validated('performers') ?? []);
            }
        });

        return back()->with('success', __('goals.messages.step_saved'));
    }

    private function destroy(Step $step): RedirectResponse
    {
        $step->delete();

        return back()->with('success', __('goals.messages.step_deleted'));
    }
}
