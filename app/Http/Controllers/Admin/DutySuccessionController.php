<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Duties\FoldPredecessorIntoRename;
use App\Http\Controllers\AdminController;
use App\Http\Requests\FoldDutyRenameRequest;
use App\Http\Requests\StoreDutyPredecessorRequest;
use App\Models\Duty;
use App\Models\Pivots\DutySuccession;
use Illuminate\Http\RedirectResponse;

/**
 * Keeps a duty's track record across splits and merges, e.g. a combined coordinator split in
 * two: the old duty stays with its own holders, linked to the duties that took over and from when.
 */
class DutySuccessionController extends AdminController
{
    public function store(StoreDutyPredecessorRequest $request, Duty $duty): RedirectResponse
    {
        $duty->predecessors()->attach($request->predecessor()->getKey(), ['effective_on' => $request->validated('effective_on')]);

        return back()->with('success', __('Ankstesnė pareigybė susieta.'));
    }

    public function destroy(Duty $duty, string $succession): RedirectResponse
    {
        $this->handleAuthorization('update', $duty);

        static::linkOf($duty, $succession)->delete();

        return back()->with('success', __('Ankstesnė pareigybė atsieta.'));
    }

    public function foldRename(FoldDutyRenameRequest $request, Duty $duty): RedirectResponse
    {
        FoldPredecessorIntoRename::execute($request->link(), $request->user());

        return back()->with('success', __('Ankstesnė pareigybė sujungta su šia kaip senas pavadinimas.'));
    }

    /** Resolved through the duty so a crafted id cannot reach another duty's history. */
    public static function linkOf(Duty $duty, string $succession): DutySuccession
    {
        return DutySuccession::query()->where('successor_id', $duty->id)->findOrFail($succession);
    }
}
