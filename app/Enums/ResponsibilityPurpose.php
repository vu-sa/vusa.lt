<?php

namespace App\Enums;

use App\Models\Institution;
use App\Services\InstitutionScopeResolver;

/**
 * Why the app looks for a responsibility's holders. At most one responsibility carries each
 * purpose; code finds people through it, never by a duty type slug or a role id.
 *
 * A closed list: each case has its own consumers, so adding one is a code change. A purpose
 * adds no permission records; consumers may allow specific access for the purpose.
 */
enum ResponsibilityPurpose: string
{
    /** Studentų atstovavimas: meeting representatives, task fallback, activity requests, metrics. */
    case StudentRepresentation = 'student_representation';

    /** Studentų atstovų koordinavimas: the person VU body representatives turn to. */
    case StudentRepCoordination = 'student_rep_coordination';

    /** Narių registracija: who receives and may read a padalinys' member registrations. */
    case MemberRegistration = 'member_registration';

    case ReservationHandling = 'reservation_handling';

    /**
     * Levels a grant may cover. Empty means the purpose applies where the duty sits.
     *
     * @return list<ResponsibilityScope>
     */
    public function allowedScopes(): array
    {
        return match ($this) {
            self::StudentRepresentation => [],
            self::StudentRepCoordination => [ResponsibilityScope::Tenant, ResponsibilityScope::InstitutionType, ResponsibilityScope::Institution],
            self::MemberRegistration, self::ReservationHandling => [ResponsibilityScope::Tenant],
        };
    }

    public function isScoped(): bool
    {
        return $this->allowedScopes() !== [];
    }

    /**
     * Whether the purpose can cover this institution at all.
     */
    public function appliesTo(Institution $institution): bool
    {
        return match ($this) {
            self::StudentRepCoordination => app(InstitutionScopeResolver::class)->forInstitution($institution) === InstitutionScope::University,
            default => true,
        };
    }

    /**
     * What every holder needs to do this work; a holder lacking any of it is reported, never granted.
     *
     * @return list<string>
     */
    public function minimumPermissions(): array
    {
        return match ($this) {
            self::ReservationHandling => ['resources.update.padalinys', 'reservations.read.padalinys'],
            self::StudentRepCoordination => [
                'institutions.read.padalinys',
                'meetings.read.padalinys',
                'meetings.update.padalinys',
                'agendaItems.update.padalinys',
                'users.read.padalinys',
            ],
            default => [],
        };
    }

    public function labelKey(): string
    {
        return "responsibilities.purposes.{$this->value}.label";
    }

    public function drivesKey(): string
    {
        return "responsibilities.purposes.{$this->value}.drives";
    }
}
