<?php

namespace App\Actions\Duties;

use App\Actions\MergeDuties;
use App\Models\DutyName;
use App\Models\Pivots\DutySuccession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Undoes a rename that was recorded as a new duty: the predecessor's terms move onto the
 * successor and its name becomes a former name, valid until the day before the link.
 */
class FoldPredecessorIntoRename
{
    /** Only a one-to-one link inside one institution can have been a plain rename. */
    public static function isEligible(DutySuccession $link): bool
    {
        $predecessor = $link->predecessor;
        $successor = $link->successor;

        if ($predecessor === null || $successor === null || $predecessor->institution_id !== $successor->institution_id) {
            return false;
        }

        $day = $link->effective_on->toDateString();

        return DutySuccession::query()->where('predecessor_id', $predecessor->id)->whereDate('effective_on', $day)->count() === 1
            && DutySuccession::query()->where('successor_id', $successor->id)->whereDate('effective_on', $day)->count() === 1;
    }

    public static function execute(DutySuccession $link, ?User $actor = null): void
    {
        DB::transaction(function () use ($link, $actor): void {
            $predecessor = $link->predecessor;
            $successor = $link->successor;

            DutyName::query()->create([
                'duty_id' => $successor->id,
                'name' => $predecessor->getTranslations('name'),
                'used_until' => $link->effective_on->copy()->subDay()->toDateString(),
            ]);

            $link->delete();

            MergeDuties::execute($successor, collect([$predecessor]), $actor);
        });
    }
}
