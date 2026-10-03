<?php

namespace App\Actions;

use App\Http\Requests\IndexFormRequest;
use App\Models\Form;
use App\Models\User;
use App\Services\FormAccessService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;

final class BuildFormIndexQuery
{
    /** @return Builder<Form> */
    public static function execute(IndexFormRequest $request, User $user, FormAccessService $formAccess, TanstackTableService $tableService): Builder
    {
        $query = Form::query()->with('tenant:id,shortname')->withCount('registrations');

        $filters = $request->getFilters();

        $tenantValues = $request->input('tenant') ?? $request->input('tenant.id') ?? ($filters['tenant.id'] ?? ($filters['tenant'] ?? null));
        if (! empty($tenantValues)) {
            $query->whereIn('tenant_id', (array) $tenantValues);
        }

        return $formAccess->applyIndexVisibility($query, $user);
    }
}
