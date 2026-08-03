<?php

namespace App\Http\Traits;

use App\Contracts\GuardsForceDelete;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait HasTanstackTables
{
    /**
     * Apply filters and search to a query builder for TanStack tables
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query  The query builder to apply filters to
     * @param  Request  $request  The request object containing filters and sorting
     * @param  TanstackTableService  $tableService  The service to apply filters with
     * @param  array  $searchableColumns  Columns to search in
     * @param  array  $options  Additional options
     * @return Builder<TModel>
     */
    protected function applyTanstackFilters(
        Builder $query,
        Request $request,
        TanstackTableService $tableService,
        array $searchableColumns = [],
        array $options = []
    ): Builder {
        // Use getSorting method if available, otherwise decode sorting from request
        $sorting = method_exists($request, 'getSorting')
            ? $request->getSorting()
            : $this->decodeSorting($request->input('sorting'));

        // Apply sorting if provided
        if (! empty($sorting)) {
            $query = $tableService->applySorting($query, $sorting);
        }

        // Apply search if provided
        if ($request->has('search') && ! empty($searchableColumns)) {
            $query = $tableService->applyGlobalSearch($query, $request->input('search'), $searchableColumns);
        }

        // Apply filters if provided
        if ($request->has('filters')) {
            // Use getFilters method if available, otherwise decode filters from request
            $filters = method_exists($request, 'getFilters')
                ? $request->getFilters()
                : $this->decodeFilters($request->input('filters'));

            if (! empty($filters)) {
                $query = $tableService->applyFiltering($query, $filters);
            }
        }

        // Apply soft delete filter if showDeleted is present
        if ($request->has('showDeleted')) {
            $query = $tableService->applySoftDeleteFilter($query, $request->boolean('showDeleted'));
        }

        // Apply permission-based filtering using the ModelAuthorizer
        if (isset($options['tenantRelation']) && isset($options['permission'])) {
            $authorizer = app(ModelAuthorizer::class);
            $query = $tableService->applyPermissionFiltering(
                $query,
                $options['tenantRelation'],
                $options['permission'],
                $authorizer
            );
        }

        return $query;
    }

    /**
     * Count the soft-deleted records reachable through an already-filtered query.
     *
     * Pass the query returned by {@see applyTanstackFilters()} so the badge shown
     * on the "Show deleted" toggle matches exactly what the trashed view will list
     * (same search, filters and tenant/permission scoping).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * Callers must pass the query *after* every scoping constraint they intend to
     * count against — including any tenant scoping a controller applies itself. When
     * the trash view is open the query already carries `onlyTrashed()`, so the count
     * re-states that constraint; it is idempotent in effect and deliberately kept
     * this way rather than counting from an earlier, less-scoped builder.
     *
     * @param  Builder<TModel>  $query  The filtered query builder
     */
    protected function getTrashedCount(Builder $query, ?TanstackTableService $tableService = null): int
    {
        return ($tableService ?? app(TanstackTableService::class))->getTrashedCount($query);
    }

    /**
     * Prepare a trash view to explain why permanent deletion is refused for each row.
     *
     * Only applies while the trash view is open, because that is the only place the
     * action is offered — and because {@see GuardsForceDelete} models
     * would otherwise run a count query per relation per row on every index request.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $relations  relations the model's reason builder counts
     * @return Builder<TModel>
     */
    protected function withForceDeleteBlockers(Builder $query, Request $request, array $relations = []): Builder
    {
        if (! $request->boolean('showDeleted')) {
            return $query;
        }

        return $relations === [] ? $query : $query->withCount($relations);
    }

    /**
     * Serialize the refusal reason onto each row of a trash view.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, TModel>  $rows
     * @return Collection<int, TModel>
     */
    protected function appendForceDeleteBlockedReason(Collection $rows, Request $request): Collection
    {
        if (! $request->boolean('showDeleted')) {
            return $rows;
        }

        return $rows->each(function ($row): void {
            if ($row instanceof GuardsForceDelete) {
                $row->append('force_delete_blocked_reason');
            }
        });
    }

    /**
     * Safely decode sorting from JSON string
     */
    private function decodeSorting(?string $sorting): array
    {
        if (empty($sorting)) {
            return [];
        }

        try {
            $decoded = json_decode($sorting, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * Safely decode filters from JSON string
     */
    private function decodeFilters(?string $filters): array
    {
        if (empty($filters)) {
            return [];
        }

        try {
            $decoded = json_decode($filters, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Exception) {
            return [];
        }
    }
}
