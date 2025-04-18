<?php

namespace App\Http\Traits;

use App\Services\DataTableService;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasTanstackTables
{
    /**
     * Apply filters and search to a query builder for TanStack tables
     *
     * @param  Builder  $query  The query builder to apply filters to
     * @param  Request  $request  The request object containing filters and sorting
     * @param  TanstackTableService|DataTableService  $tableService  The service to apply filters with
     * @param  array  $searchableColumns  Columns to search in
     * @param  array  $options  Additional options
     */
    protected function applyTanstackFilters(
        Builder $query,
        Request $request,
        $tableService,
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return [];
        }
    }
}
