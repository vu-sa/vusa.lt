<?php

namespace App\Actions\Dutiables;

use App\Actions\ResolveExOfficioTenantId;
use App\Models\Pivots\Dutiable;
use App\Services\DutyAssignmentService;

class LinkDutiableToExOfficioSource
{
    public static function execute(Dutiable $row, Dutiable $source): void
    {
        abort_unless($row->via_dutiable_id === null && ! $row->derivedDutiables()->exists()
            && ! $row->ended_by_institution_deactivation && $source->id !== $row->id
            && $source->via_dutiable_id === null && $source->dutiable_id === $row->dutiable_id
            && $source->dutiable_type === $row->dutiable_type && ! $source->ended_by_institution_deactivation
            && $source->duty?->exOfficioTargetDuties()->whereKey($row->duty_id)->exists(), 422);
        abort_unless(app(DutyAssignmentService::class)->lockExOfficioContext($source, $row->duty), 422);
        $row->update([
            'assignment_origin' => 'ex_officio', 'via_dutiable_id' => $source->id,
            'tenant_id' => ResolveExOfficioTenantId::execute($source, $row->duty),
            'start_date' => $source->start_date, 'end_date' => $source->end_date,
        ]);
    }
}
