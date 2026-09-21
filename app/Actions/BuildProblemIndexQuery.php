<?php

namespace App\Actions;

use App\Http\Requests\IndexProblemRequest;
use App\Models\Problem;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;

final class BuildProblemIndexQuery
{
    /** @return Builder<Problem> */
    public static function execute(IndexProblemRequest $request, ModelAuthorizer $authorizer, TanstackTableService $tableService): Builder
    {
        $query = Problem::query()->with(['tenant', 'createdBy', 'responsibleUser', 'categories', 'institutions']);

        $query = $tableService->applyPermissionFiltering(
            $query,
            'tenant',
            'problems.read.padalinys',
            $authorizer
        );

        $filters = $request->getFilters();

        // Status filter: accept both array/string from filters JSON and direct request query
        $statusValues = $request->input('status') ?? ($filters['status'] ?? null);
        if (! empty($statusValues)) {
            $query->whereIn('status', (array) $statusValues);
        }

        // Category filter
        $categoryValues = $request->input('category') ?? ($filters['category'] ?? null);
        if (! empty($categoryValues)) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('problem_categories.id', (array) $categoryValues));
        }

        // Institution filter
        $institutionValues = $request->input('institution') ?? ($filters['institution'] ?? null);
        if (! empty($institutionValues)) {
            $query->whereHas('institutions', fn ($q) => $q->whereIn('institutions.id', (array) $institutionValues));
        }

        // Tenant filter
        $tenantValues = $request->input('tenant') ?? $request->input('tenant.id') ?? ($filters['tenant.id'] ?? ($filters['tenant'] ?? null));
        if (! empty($tenantValues)) {
            $query->whereIn('tenant_id', (array) $tenantValues);
        }

        // Creator filter
        $creatorValues = $request->input('created_by') ?? ($filters['created_by'] ?? null);
        if (! empty($creatorValues)) {
            $query->whereIn('created_by', (array) $creatorValues);
        }

        return $query;
    }
}
