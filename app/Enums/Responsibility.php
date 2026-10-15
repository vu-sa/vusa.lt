<?php

namespace App\Enums;

use App\Models\Institution;
use App\Services\InstitutionScopeResolver;

/**
 * What a duty is responsible for handling, as opposed to what its roles allow it to do.
 *
 * A closed list: each case is business logic with its own consumers, so adding one is a code
 * change, never admin data. A responsibility never grants broad access; policies may only let
 * its holder act on the subject they are responsible for.
 */
enum Responsibility: string
{
    /** Studentų atstovų koordinavimas: the person VU body representatives turn to. */
    case StudentRepCoordination = 'student_rep_coordination';

    /**
     * @return list<ResponsibilityScope>
     */
    public function allowedScopes(): array
    {
        return match ($this) {
            self::StudentRepCoordination => [ResponsibilityScope::Tenant, ResponsibilityScope::InstitutionType, ResponsibilityScope::Institution],
        };
    }

    /**
     * Whether the responsibility can cover this institution at all.
     */
    public function appliesTo(Institution $institution): bool
    {
        return match ($this) {
            self::StudentRepCoordination => app(InstitutionScopeResolver::class)->forInstitution($institution) === InstitutionScope::University,
        };
    }

    public function labelKey(): string
    {
        return "responsibilities.types.{$this->value}.label";
    }
}
