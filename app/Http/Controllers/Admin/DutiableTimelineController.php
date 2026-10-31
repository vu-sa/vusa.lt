<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Dutiables\ApplyDutiableTimelineChanges;
use App\Actions\Dutiables\LinkDutiableToExOfficioSource;
use App\Actions\Dutiables\MergeDutiables;
use App\Actions\Dutiables\PlanDutiableTimelineChanges;
use App\Actions\Dutiables\PreviewDutiableMerge;
use App\Http\Controllers\AdminController;
use App\Http\Requests\Dutiables\ApplyDutiableTimelineRequest;
use App\Http\Requests\Dutiables\IndexDutiableTimelineRequest;
use App\Http\Requests\Dutiables\MergeDutiablesRequest;
use App\Http\Requests\Dutiables\ReviewExOfficioRequest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Http\JsonResponse;
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
     * two visits never disagree. A member may always `view` their own institution
     * (InstitutionPolicy baseline), so none needs filtering out.
     *
     * @return Collection<int, Institution>
     */
    private function ownInstitutions(User $user): Collection
    {
        return $user->authorization_duties()
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

    public function reviewExOfficio(ReviewExOfficioRequest $request, Dutiable $dutiable): RedirectResponse
    {
        $mutation = fn () => DB::transaction(function () use ($request, $dutiable): void {
            $dutiable = $dutiable->newQuery()->lockForUpdate()->findOrFail($dutiable->id);
            $this->authorize('manageDutiable', $dutiable);
            abort_if($dutiable->via_dutiable_id !== null || $dutiable->ended_by_institution_deactivation, 422);
            if ($request->validated('decision') === 'manual') {
                $dutiable->update(['assignment_origin' => 'manual']);
            } else {
                $source = Dutiable::query()->lockForUpdate()->findOrFail($request->validated('source_id'));
                $this->authorize('manageDutiable', $source);
                LinkDutiableToExOfficioSource::execute($dutiable, $source);
            }
            activity()->performedOn($dutiable)->causedBy($request->user())->event('ex_officio_reviewed')
                ->withProperties($request->safe()->only(['decision', 'source_id']))->log('ex_officio_reviewed');
        });

        if ($warning = $this->guardSelfLockout($request->user(), ! $request->user()->isSuperAdmin()
            && $dutiable->dutiable_id === $request->user()->id, $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', __('dutiables.timeline.review.saved'));
    }

    public function previewMerge(MergeDutiablesRequest $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => PreviewDutiableMerge::execute($request->rows())]);
    }

    /**
     * Folds several stints of one holder into one row. Separate from `apply()` because a
     * merge deletes rows, which no operation in the planner's vocabulary can express.
     */
    public function merge(MergeDutiablesRequest $request): RedirectResponse
    {
        $rows = $request->rows();

        $mutation = fn () => DB::transaction(function () use ($rows, $request): void {
            $locked = Dutiable::query()->whereKey($rows->modelKeys())->orderBy('id')->lockForUpdate()->get();
            abort_unless($locked->count() === $rows->count() && MergeDutiables::isMergeable($locked), 422);
            foreach ($locked as $row) {
                $this->authorize('manageDutiable', $row);
            }
            if ($request->filled('fingerprint')) {
                abort_unless(hash_equals(PreviewDutiableMerge::execute($locked)['fingerprint'], $request->validated('fingerprint')), 409);
            }
            MergeDutiables::execute($locked);
        });

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
