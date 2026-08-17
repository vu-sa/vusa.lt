<?php

namespace App\Http\Requests\Concerns;

use App\Actions\GetTenantsForUpserts;
use App\Services\ModelAuthorizer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

/**
 * Constrains a request-supplied `tenant_id` to the tenants the acting user may actually
 * create in.
 *
 * HasCommonChecks::create() is deliberately tenant-agnostic — it only asks whether the user
 * holds `{resource}.create.padalinys` *somewhere*. That makes a bare `can('create', X::class)`
 * insufficient on any store endpoint that reads its owning tenant from the payload: a
 * coordinator for one padalinys could otherwise create records inside another one.
 *
 * Precedent for the underlying pattern: StoreDutyRequest::withValidator() and
 * PreviewContentPartsRequest.
 */
trait ValidatesTenantScope
{
    /**
     * A `Rule::in` over the tenant ids the user may create the given resource in.
     *
     * @param  string  $permission  e.g. 'resources.create.padalinys'
     */
    protected function tenantIdInAuthorizedScope(string $permission): In
    {
        return Rule::in($this->authorizedTenantIds($permission));
    }

    /**
     * @return array<int, int>
     */
    protected function authorizedTenantIds(string $permission): array
    {
        return GetTenantsForUpserts::execute($permission, app(ModelAuthorizer::class))
            ->pluck('id')
            ->map(intval(...))
            ->all();
    }
}
