<?php

namespace App\Services\Permissions;

use App\Events\DutiableChanged;
use App\Facades\Permission;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
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

        $deferred = [];

        try {
            // Fake only DutiableChanged: this mutation is speculative and may be rolled
            // back below, so its listeners must not run — they would sync ex-officio rows
            // and bust permission caches against state that never lands. Other model
            // observers still run, so type -> role effects are reflected in the snapshot.
            $deferred = $this->captureDutiableEvents($mutation);

            // Reset caches so the "after" snapshot reflects the just-applied
            // (still uncommitted) state rather than memoised pre-change data.
            Permission::resetCache($actingUser);

            $after = CapabilitySnapshot::capture($actingUser->fresh());
            $report = AccessChangeReport::diff($before, $after);

            if ($shouldBlock($report)) {
                DB::rollBack();
                $deferred = [];
            } else {
                DB::commit();
            }
        } catch (Throwable $e) {
            DB::rollBack();
            Permission::resetCache($actingUser);

            throw $e;
        }

        // Deferring is not the same as dropping: a committed mutation must still
        // reach the listeners it was held back from, or its ex-officio rows are
        // never synced and the holder's permission caches stay stale. Replay now
        // that the rows the listeners will re-read are actually persisted.
        foreach ($deferred as $event) {
            Event::dispatch($event);
        }

        // Whether committed or rolled back, drop any cache the snapshots warmed
        // so the live request recomputes against the real persisted state.
        Permission::resetCache($actingUser);

        return $report;
    }

    /**
     * Run $mutation with DutiableChanged intercepted, returning the events it
     * would have dispatched so the caller can replay them if the work commits.
     *
     * @param  Closure():mixed  $mutation
     * @return array<int, DutiableChanged>
     */
    private function captureDutiableEvents(Closure $mutation): array
    {
        // Event::fakeFor() would restore the dispatcher for us but hands back the
        // callable's return value, not the fake — and the fake is the whole point
        // here. Restoring it by hand mirrors what fakeFor() does internally:
        // Eloquent and the cache repository hold their own dispatcher references.
        $originalDispatcher = Event::getFacadeRoot();
        $fake = Event::fake([DutiableChanged::class]);

        try {
            $mutation();
        } finally {
            Event::swap($originalDispatcher);
            Model::setEventDispatcher($originalDispatcher);
            Cache::refreshEventDispatcher();
        }

        return $fake->dispatched(DutiableChanged::class)
            ->map(fn (array $arguments) => $arguments[0])
            ->all();
    }
}
