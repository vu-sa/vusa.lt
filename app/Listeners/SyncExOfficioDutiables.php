<?php

namespace App\Listeners;

use App\Actions\ResolveExOfficioTenantId;
use App\Events\DutiableChanged;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Support\MorphMap;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SyncExOfficioDutiables implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Dutiable rows are written inside DB::transaction() by every admin path that
     * assigns a duty. Without this the job is queued the moment the event fires, so
     * a worker can dequeue it before the transaction commits, find no row for
     * `dutiableRowId`, and return having synced nothing — an ex-officio seat that
     * silently fails to appear, depending purely on worker timing.
     */
    public $afterCommit = true;

    public function handle(DutiableChanged $event): void
    {
        // Only sync User dutiables; contacts and other morphable types are excluded.
        if ($event->dutiableType !== MorphMap::alias(User::class)) {
            return;
        }

        // Only process root-source rows. Derived rows (via_dutiable_id set) do not
        // themselves trigger further syncing — this prevents infinite loops and cycles.
        if (! is_null($event->viaDutiableId)) {
            return;
        }

        if ($event->wasDeleted) {
            // The row was deleted — clean up all rows derived from it.
            $this->handleDeleted($event->dutiableRowId);

            return;
        }

        $dutiable = Dutiable::with('duty.institution', 'duty.exOfficioTargetDuties.assignableTenants')
            ->find($event->dutiableRowId);

        // Deleted between dispatch and handling — nothing left to sync.
        if (! $dutiable) {
            return;
        }

        $this->handleSaved($dutiable);
    }

    private function handleSaved(Dutiable $dutiable): void
    {
        $targetDuties = $dutiable->duty->exOfficioTargetDuties ?? collect();

        if ($targetDuties->isEmpty()) {
            return;
        }

        $userId = $dutiable->dutiable_id;

        foreach ($targetDuties as $targetDuty) {
            $this->syncDerivedRow($dutiable, $userId, $targetDuty);
        }
    }

    private function handleDeleted(string $sourceId): void
    {
        // All derived rows (created OR adopted) are deleted along with the source.
        Dutiable::where('via_dutiable_id', $sourceId)->delete();
    }

    public function syncDerivedRow(Dutiable $source, string $userId, Duty $targetDuty): void
    {
        DB::transaction(function () use ($source, $userId, $targetDuty): void {
            if (! $source->duty()->whereHas('exOfficioTargetDuties', fn ($query) => $query->whereKey($targetDuty->id))->exists()
                || ! app(DutyAssignmentService::class)->lockExOfficioContext($source, $targetDuty)) {
                return;
            }

            $this->syncActiveTarget($source, $userId, $targetDuty);
        });
    }

    private function syncActiveTarget(Dutiable $source, string $userId, Duty $targetDuty): void
    {
        $targetDutyId = $targetDuty->id;
        $resolvedTenantId = ResolveExOfficioTenantId::execute($source, $targetDuty);

        // If a derived row for this source already exists, mirror the dates and tenant_id.
        $derivedRows = Dutiable::where('via_dutiable_id', $source->id)
            ->where('duty_id', $targetDutyId)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where('dutiable_id', $userId)
            ->get();

        foreach ($derivedRows as $derived) {
            if ($derived->ended_by_institution_deactivation) {
                continue;
            }
            $derived->start_date = $source->start_date;
            $derived->end_date = $source->end_date;
            $derived->tenant_id = $resolvedTenantId;
            $derived->save();
        }

        if ($derivedRows->isNotEmpty() || $source->ended_by_institution_deactivation) {
            return;
        }

        // Create a new derived row.
        Dutiable::create([
            'duty_id' => $targetDutyId,
            'dutiable_id' => $userId,
            'dutiable_type' => MorphMap::alias(User::class),
            'via_dutiable_id' => $source->id,
            'tenant_id' => $resolvedTenantId,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
        ]);
    }
}
