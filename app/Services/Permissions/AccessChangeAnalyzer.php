<?php

namespace App\Services\Permissions;

use App\Events\DutiableChanged;
use App\Facades\Permission;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Measures how a proposed mutation would affect the acting user's own access,
 * and decides whether to keep it.
 *
 * Rather than re-implementing the permission engine, the analyzer runs the real
 * mutation inside a transaction, re-queries the authorization stack, and then
 * commits it — unless the change would critically reduce the acting user's own
 * access, in which case it is rolled back and reported. Running the mutation
 * exactly once keeps it correct as the permission resolution logic evolves and
 * avoids re-execution hazards (e.g. a deleted model's stale `exists` state).
 */
class AccessChangeAnalyzer
{
    /**
     * Run $mutation and persist it, unless $shouldBlock deems the resulting role
     * loss unacceptable — in which case it is rolled back. Either way the returned
     * report describes the measured impact; callers re-apply their predicate to
     * know whether anything was persisted.
     *
     * @param  Closure():mixed  $mutation
     * @param  (Closure(AccessChangeReport): bool)|null  $shouldBlock  Defaults to blocking any role loss
     */
    public function apply(User $actingUser, Closure $mutation, ?Closure $shouldBlock = null): AccessChangeReport
    {
        $shouldBlock ??= fn (AccessChangeReport $report) => $report->isCritical();

        $before = CapabilitySnapshot::capture($actingUser);

        DB::beginTransaction();

        try {
            // Fake only DutiableChanged so the measured mutation doesn't fire its
            // queued cache-invalidation listener (which would also choke trying to
            // restore a just-deleted pivot). Other model observers still run, so
            // type -> role effects are reflected in the snapshot below.
            Event::fakeFor(fn () => $mutation(), [DutiableChanged::class]);

            // Reset caches so the "after" snapshot reflects the just-applied
            // (still uncommitted) state rather than memoised pre-change data.
            Permission::resetCache($actingUser);

            $after = CapabilitySnapshot::capture($actingUser->fresh());
            $report = AccessChangeReport::diff($before, $after);

            if ($shouldBlock($report)) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            Permission::resetCache($actingUser);

            throw $e;
        }

        // Whether committed or rolled back, drop any cache the snapshots warmed
        // so the live request recomputes against the real persisted state.
        Permission::resetCache($actingUser);

        return $report;
    }
}
