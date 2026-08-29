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
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
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

        $ownInstitutions = $this->ownInstitutions($request->user());

        return $this->inertiaResponse('Admin/People/DutiableTimeline', [
            // Landing on an empty state is a dead end for someone who only ever looks at
            // their own body, so the page opens on it. The client may still override this
            // from the institution it remembers.
            'initialInstitution' => ($institution ?? $ownInstitutions->first())?->only(['id', 'name', 'alias']),
            'userInstitutions' => $ownInstitutions
                ->map(fn (Institution $own) => $own->only(['id', 'name', 'alias']))
                ->values()
                ->all(),
        ]);
    }

    /**
     * The institutions the actor themselves sits in, busiest first.
     *
     * Most people hold more than one duty, so "their" institution is a ranking rather than
     * a lookup: the one they hold the most current duties in wins, ties go alphabetically so
     * two visits never disagree. Anything they may not `view` is dropped — the page would
     * only 403 on the first fetch otherwise.
     *
     * @return Collection<int, Institution>
     */
    private function ownInstitutions(User $user): Collection
    {
        return $user->current_duties()
            ->with('institution')
            ->get()
            ->pluck('institution')
            ->filter()
            ->groupBy('id')
            // One sort key rather than a comparison list: Collection::sortBy() treats a
            // closure inside an array as a comparator, not as a key, which silently sorts
            // by something else entirely.
            ->sortBy(fn (Collection $held) => sprintf(
                '%04d|%s',
                9999 - $held->count(),
                mb_strtolower((string) $held->first()->name),
            ))
            ->map(fn (Collection $held) => $held->first())
            ->filter(fn (Institution $institution) => $user->can('view', $institution))
            ->values();
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
