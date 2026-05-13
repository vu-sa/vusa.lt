<?php

namespace App\Listeners;

use App\Events\DutiableChanged;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SyncExOfficioDutiables implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(DutiableChanged $event): void
    {
        $dutiable = $event->dutiable;

        // Only sync User dutiables; contacts and other morphable types are excluded.
        if ($dutiable->dutiable_type !== User::class) {
            return;
        }

        // Only process root-source rows. Derived rows (via_dutiable_id set) do not
        // themselves trigger further syncing — this prevents infinite loops and cycles.
        if (! is_null($dutiable->via_dutiable_id)) {
            return;
        }

        if (! $dutiable->exists) {
            // The row was force-deleted — remove or clean up all derived rows.
            $this->handleDeleted($dutiable);

            return;
        }

        $this->handleSaved($dutiable);
    }

    private function handleSaved(Dutiable $dutiable): void
    {
        $dutiable->load('duty.exOfficioTargetDuties');

        $targetDuties = $dutiable->duty->exOfficioTargetDuties ?? collect();

        if ($targetDuties->isEmpty()) {
            return;
        }

        $userId = $dutiable->dutiable_id;

        foreach ($targetDuties as $targetDuty) {
            $this->syncDerivedRow($dutiable, $userId, $targetDuty->id);
        }
    }

    private function handleDeleted(Dutiable $dutiable): void
    {
        // All derived rows (created OR adopted) are deleted along with the source.
        Dutiable::where('via_dutiable_id', $dutiable->id)->delete();
    }

    private function syncDerivedRow(Dutiable $source, string $userId, string $targetDutyId): void
    {
        // If a derived row for this source already exists, mirror the dates.
        $derived = Dutiable::where('via_dutiable_id', $source->id)
            ->where('duty_id', $targetDutyId)
            ->where('dutiable_type', User::class)
            ->where('dutiable_id', $userId)
            ->first();

        if ($derived) {
            $derived->start_date = $source->start_date;
            $derived->end_date = $source->end_date;
            $derived->save();

            return;
        }

        // Adopt an existing active manual row for this user+target duty.
        $manual = Dutiable::where('duty_id', $targetDutyId)
            ->where('dutiable_type', User::class)
            ->where('dutiable_id', $userId)
            ->whereNull('via_dutiable_id')
            ->whereNull('end_date')
            ->first();

        if ($manual) {
            $manual->via_dutiable_id = $source->id;
            $manual->start_date = $source->start_date;
            $manual->end_date = $source->end_date;
            $manual->save();

            return;
        }

        // Create a new derived row.
        Dutiable::create([
            'duty_id' => $targetDutyId,
            'dutiable_id' => $userId,
            'dutiable_type' => User::class,
            'via_dutiable_id' => $source->id,
            'start_date' => $source->start_date,
            'end_date' => $source->end_date,
        ]);
    }
}
