<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;

/**
 * Determine the tenant_id that should be set on a derived Dutiable row
 * created by an ex-officio assignment.
 *
 * If the target duty supports the source duty's tenant (i.e. the source
 * tenant appears in the target duty's assignableTenants), the derived
 * row receives that tenant_id so it counts as a cross-tenant rep.
 * Otherwise the derived row gets tenant_id = null.
 */
class ResolveExOfficioTenantId
{
    public static function execute(Dutiable $source, Duty $targetDuty): ?int
    {
        $sourceTenantId = $source->duty?->institution?->tenant_id;

        if (! $sourceTenantId) {
            return null;
        }

        $targetDuty->loadMissing('assignableTenants');

        return $targetDuty->assignableTenants->contains('id', $sourceTenantId)
            ? $sourceTenantId
            : null;
    }
}
