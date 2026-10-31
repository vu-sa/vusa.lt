<?php

namespace App\Actions\Dutiables;

use App\Models\Pivots\Dutiable;
use App\Services\DutyAssignmentService;
use App\Support\Dutiables\DutiablePeriods;
use App\Support\Dutiables\ExOfficioSeatDates;

class LinkDutiableToExOfficioSource
{
    public static function execute(Dutiable $row, Dutiable $source, ?array $period = null): void
    {
        abort_unless($row->via_dutiable_id === null && ! $row->derivedDutiables()->exists()
            && ! $row->ended_by_institution_deactivation && $source->id !== $row->id
            && $source->via_dutiable_id === null && $source->dutiable_id === $row->dutiable_id
            && $source->dutiable_type === $row->dutiable_type && ! $source->ended_by_institution_deactivation, 422);
        abort_unless(app(DutyAssignmentService::class)->lockExOfficioContext($source, $row->duty), 422);
        abort_unless($source->duty->exOfficioTargetDuties()->whereKey($row->duty_id)->exists(), 422);
        $desired = ExOfficioSeatDates::forTarget($source, $row->duty_id,
            $source->derivedDutiables()->with('media')->get(), today()->toDateString())['dates'];
        abort_unless($desired !== null && DutiablePeriods::overlap($desired, $period ?? DutiablePeriods::dates($row)) > 0, 422);
        $row->update(['assignment_origin' => 'ex_officio', 'via_dutiable_id' => $source->id]);
        ReconcileExOfficioSeats::forSource($source, includeHistory: true, targetId: $row->duty_id);
    }
}
