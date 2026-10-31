<?php

namespace App\Actions\Dutiables;

use App\Actions\ResolveExOfficioTenantId;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\Dutiables\DutiablePeriods;
use App\Support\Dutiables\ExOfficioSeatDates;
use App\Support\MorphMap;

/** Compares stored seats with the shared date calculation without applying repairs or inventing historical gaps. */
class CheckExOfficioIntegrity
{
    public const string CACHE_KEY = 'duties.ex_officio_integrity';

    public static function execute(): array
    {
        $findings = [];
        Dutiable::query()->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNull('via_dutiable_id')->where('ended_by_institution_deactivation', false)
            ->with(['duty.institution', 'duty.exOfficioTargetDuties.assignableTenants',
                'duty.exOfficioTargetDuties.institution', 'derivedDutiables.media'])
            ->orderBy('id')->each(function (Dutiable $source) use (&$findings): void {
                array_push($findings, ...self::findingsForSource($source));
            });
        Dutiable::query()->whereNotNull('via_dutiable_id')->current()->with('viaDutiable.duty.exOfficioTargetDuties')->orderBy('id')
            ->each(function (Dutiable $seat) use (&$findings): void {
                if ($seat->viaDutiable?->via_dutiable_id !== null
                    && $seat->viaDutiable->duty?->exOfficioTargetDuties->contains('id', $seat->duty_id)) {
                    $findings[] = ['kind' => 'chain', 'row_id' => $seat->id, 'source_id' => $seat->via_dutiable_id];
                }
            });

        return ['checked_at' => now()->toIso8601String(), 'count' => count($findings), 'findings' => $findings];
    }

    private static function findingsForSource(Dutiable $source): array
    {
        // The relation hides trashed duties, whose terms still exist.
        if ($source->duty === null) {
            return [];
        }
        $seats = $source->derivedDutiables;
        $findings = [];
        foreach ($source->duty->exOfficioTargetDuties as $target) {
            $expected = ExOfficioSeatDates::forTarget($source, $target->id, $seats, today()->toDateString());
            $dates = $expected['dates'];
            $seat = $expected['seat'];
            if ($dates !== null) {
                if ($seat !== null) {
                    array_push($findings, ...self::findingsForUpdate($source, $seat, $target, $dates));
                } elseif (self::periodGrantsAccess($dates) && self::institutionsActive($source, $target)) {
                    $findings[] = ['kind' => 'missing_seat', 'source_id' => $source->id,
                        'target_duty_id' => $target->id, ...$dates];
                }
            }
            foreach ($expected['duplicates'] as $duplicate) {
                $findings[] = ['kind' => 'duplicate', 'row_id' => $duplicate->id, 'source_id' => $source->id];
            }
        }

        return $findings;
    }

    private static function findingsForUpdate(Dutiable $source, Dutiable $seat, ?Duty $target, array $update): array
    {
        $findings = [];
        if (! DutiablePeriods::grantsAccess($seat) && self::periodGrantsAccess($update)
            && self::institutionsActive($source, $target)) {
            $findings[] = ['kind' => 'missing_seat', 'source_id' => $source->id, 'target_duty_id' => $target->id, ...$update];
        }
        if (DutiablePeriods::grantsAccess($seat) && $target !== null && self::seatDrifts($source, $seat, $target, $update)) {
            $findings[] = ['kind' => 'drift', 'row_id' => $seat->id, 'source_id' => $source->id];
        }

        return $findings;
    }

    private static function periodGrantsAccess(array $period): bool
    {
        return $period['end_date'] === null || $period['end_date'] >= today()->toDateString();
    }

    private static function institutionsActive(Dutiable $source, ?Duty $target): bool
    {
        return (bool) $source->duty?->institution?->is_active && (bool) $target?->institution?->is_active;
    }

    private static function seatDrifts(Dutiable $source, Dutiable $seat, Duty $target, array $update): bool
    {
        return DutiablePeriods::dates($seat) !== ['start_date' => $update['start_date'], 'end_date' => $update['end_date']]
            || $seat->tenant_id !== ResolveExOfficioTenantId::execute($source, $target);
    }
}
