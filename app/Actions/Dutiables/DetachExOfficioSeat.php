<?php

namespace App\Actions\Dutiables;

use App\Models\Pivots\Dutiable;

class DetachExOfficioSeat
{
    public static function isOrphaned(Dutiable $seat, ?bool $hasRule = null): bool
    {
        if ($seat->via_dutiable_id === null || $seat->ended_by_institution_deactivation) {
            return false;
        }

        $hasRule ??= $seat->viaDutiable?->duty?->exOfficioTargetDuties()->whereKey($seat->duty_id)->exists() ?? false;

        return ! $hasRule;
    }

    public static function execute(Dutiable $seat): void
    {
        abort_unless(self::isOrphaned($seat), 422);

        $seat->update(['via_dutiable_id' => null, 'assignment_origin' => 'manual']);
    }
}
