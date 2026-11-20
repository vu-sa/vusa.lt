<?php

namespace App\Actions\Duties;

use App\Models\Duty;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;

class GetAssignableExOfficioDuties
{
    /**
     * Ex officio targets stay in the source duty's tenant unless the actor has global duty access.
     */
    public static function execute(User $actor, ModelAuthorizer $authorizer, ?Duty $duty = null): Collection
    {
        $query = Duty::select('id', 'name', 'institution_id', 'is_active')
            ->with(['institution:id,name,short_name,tenant_id,is_active', 'institution.tenant:id,shortname'])
            ->orderBy('name');

        if ($duty) {
            $query->where('id', '!=', $duty->id);

            if (! $authorizer->allows($actor, 'duties.update.*')) {
                $tenantId = $duty->institution?->tenant_id;
                $query->whereHas('institution', fn ($q) => $q->where('tenant_id', $tenantId));
            }
        } elseif (! $authorizer->allows($actor, 'duties.create.*')) {
            $tenantIds = $authorizer->tenants($actor, 'duties.create.padalinys')->pluck('id');
            $query->whereHas('institution', fn ($q) => $q->whereIn('tenant_id', $tenantIds));
        }

        return $query->get();
    }
}
