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
            // Suppress DutiableChanged during speculative mutation to avoid uncommitted side-effects.
            $deferred = $this->captureDutiableEvents($mutation);

            // Reset caches including globals so the post-mutation snapshot reads uncommitted pivot state.
            Permission::resetCache($actingUser, flushGlobal: true);

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
            Permission::resetCache($actingUser, flushGlobal: true);

            throw $e;
        }

        // Replay deferred DutiableChanged events once mutation is committed.
        foreach ($deferred as $event) {
            Event::dispatch($event);
        }

        // Drop any cache warmed by snapshots so subsequent calls recompute against persisted state.
        Permission::resetCache($actingUser, flushGlobal: true);

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
        // Restore original dispatcher manually to retain access to the fake instance.
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
