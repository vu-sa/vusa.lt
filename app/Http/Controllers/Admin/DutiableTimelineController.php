<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Dutiables\ApplyDutiableTimelineChanges;
use App\Actions\Dutiables\MergeDutiables;
use App\Actions\Dutiables\PlanDutiableTimelineChanges;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Dutiables\ApplyDutiableTimelineRequest;
use App\Http\Requests\Dutiables\IndexDutiableTimelineRequest;
use App\Http\Requests\Dutiables\MergeDutiablesRequest;
use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Response as InertiaResponse;

/**
 * The write half of the dutiable timeline editor.
 *
 * Deliberately not on the JSON API: guardSelfLockout() answers a self-affecting batch
 * with an `access_change_warning` Inertia flash, and useAccessChangeGuard.ts reads that
 * off usePage().props.flash. A JSON endpoint would have to reimplement the whole contract.
 */
class DutiableTimelineController extends AdminController
{
    /**
     * The institution-wide page. It is a shell: every row it can show is fetched through
     * DutiableTimelineApiController, which authorizes `view` on the chosen scope.
     *
     * Gated on `viewAny` for Duty rather than for Dutiable — `dutiables.read.padalinys`
     * is held by exactly one role, so gating on it would lock out the coordinators this
     * page exists for, while `duties.read.padalinys` is what they actually carry.
     */
    public function index(IndexDutiableTimelineRequest $request): InertiaResponse
    {
        $this->authorize('viewAny', Duty::class);

        // Loaded whole, not `select('id', 'name', 'alias')`: InstitutionPolicy scopes by
        // `tenant_id`, so a trimmed model authorizes as if it belonged to no tenant.
        $institution = $request->filled('institution')
            ? Institution::query()->find($request->validated('institution'))
            : null;

        if ($institution !== null) {
            $this->authorize('view', $institution);
        }

        return $this->inertiaResponse('Admin/People/DutiableTimeline', [
            'initialInstitution' => $institution?->only(['id', 'name', 'alias']),
        ]);
    }

    public function apply(ApplyDutiableTimelineRequest $request): RedirectResponse
    {
        // Re-planned server-side from the operation list; whatever diff the client drew is
        // advisory only.
        $plan = PlanDutiableTimelineChanges::execute($request->operations());

        $mutation = fn () => DB::transaction(fn () => ApplyDutiableTimelineChanges::execute($plan));

        // Super admins short-circuit every permission, so no date change can lock them out.
        $couldAffectSelf = ! $request->user()->isSuperAdmin()
            && $plan->touchesUser($request->user()->id);

        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return $this->redirectBack(array_filter([
            'success' => $this->entityMessage('updated', 'dutiable'),
            'info' => $plan->blockedMessage(),
        ]));
    }

    /**
     * Folds several stints of one holder into one row. Separate from `apply()` because a
     * merge deletes rows, which no operation in the planner's vocabulary can express.
     */
    public function merge(MergeDutiablesRequest $request): RedirectResponse
    {
        $rows = $request->rows();

        $mutation = fn () => DB::transaction(fn () => MergeDutiables::execute($rows));

        $couldAffectSelf = ! $request->user()->isSuperAdmin()
            && $rows->contains(fn ($row) => $row->dutiable_id === $request->user()->id);

        if ($warning = $this->guardSelfLockout($request->user(), $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return $this->redirectBack([
            'success' => __('dutiables.timeline.actions.merge_done', ['count' => $rows->count()]),
        ]);
    }
}
