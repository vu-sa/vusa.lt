<?php

namespace App\Support;

use App\Actions\GenerateInstitutionAlias;
use App\Enums\TenantType;
use App\Models\Institution;

/**
 * How prominent an institution is across VU SA: the contacts search lists by it, and a comment
 * author's duties are suggested by it. VU SA, the central office, its governing bodies, then each
 * padalinys' and PKP's main institution; everything else ties at zero.
 */
final class InstitutionPriority
{
    public static function of(Institution $institution): int
    {
        $tenant = $institution->tenant;
        $isPrimary = $tenant !== null && (string) $tenant->primary_institution_id === (string) $institution->id;
        $isMainTenant = $tenant?->type === TenantType::Pagrindinis;

        return match (true) {
            $isPrimary && $isMainTenant => 5,
            $institution->alias === GenerateInstitutionAlias::CENTRAL_OFFICE_ALIAS => 4,
            $isMainTenant && $institution->types->contains('slug', config('vusa.featured_institution_type')) => 3,
            $isPrimary && $tenant->type === TenantType::Padalinys => 2,
            $isPrimary && $tenant->type === TenantType::Pkp => 1,
            default => 0,
        };
    }
}
