<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreDutyPredecessorRequest;
use App\Models\Duty;
use Illuminate\Http\RedirectResponse;

/**
 * Keeps a duty's track record across splits and merges, e.g. a combined coordinator split in
 * two: the old duty stays with its own holders, linked to the duties that took over.
 */
class DutySuccessionController extends AdminController
{
    public function store(StoreDutyPredecessorRequest $request, Duty $duty): RedirectResponse
    {
        $duty->predecessors()->syncWithoutDetaching([$request->predecessor()->getKey()]);

        return back()->with('success', __('Ankstesnė pareigybė susieta.'));
    }

    public function destroy(Duty $duty, string $predecessor): RedirectResponse
    {
        $this->handleAuthorization('update', $duty);

        // Resolved through the duty so a crafted id cannot unlink another duty's history.
        abort_unless($duty->predecessors()->whereKey($predecessor)->exists(), 404);

        $duty->predecessors()->detach($predecessor);

        return back()->with('success', __('Ankstesnė pareigybė atsieta.'));
    }
}
