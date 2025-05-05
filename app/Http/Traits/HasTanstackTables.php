<?php

namespace App\Http\Traits;

use App\Services\DataTableService;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Scout\Builder as ScoutBuilder;

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
     * Use Laravel Scout to perform a search and apply Tanstack filtering
     *
     * @param  string  $modelClass  The fully qualified model class name
     * @param  Request  $request  The request object containing search, filters and sorting
     * @param  TanstackTableService|DataTableService  $tableService  The service to apply filters with
     * @param  array  $options  Additional options including permission settings
     */
    protected function searchWithTanstack(
        string $modelClass,
        Request $request,
        $tableService,
        array $options = []
    ) {
        // Get the search term - default to * if empty for Typesense which requires a query
        $searchTerm = $request->input('search') ?: '*';
        
        // Get sorting parameters
        $sorting = method_exists($request, 'getSorting')
            ? $request->getSorting()
            : $this->decodeSorting($request->input('sorting'));

        // Get filters
        $filters = method_exists($request, 'getFilters')
            ? $request->getFilters()
            : $this->decodeFilters($request->input('filters'));
            
        // Start a Scout search query
        $searchQuery = $modelClass::search($searchTerm);

        // Apply direct column filters
        if (!empty($filters)) {
            // Add filtering using whereIn for array values or where for single values
            foreach ($filters as $key => $value) {
                if ($value === null || (is_array($value) && empty($value))) {
                    continue;
                }
                
                if (is_array($value)) {
                    // For array values, use whereIn
                    $searchQuery->whereIn($key, $value);
                } else {
                    // For single values, use where
                    $searchQuery->where($key, $value);
                }
            }
        }
        
        // Apply sorting
        if (!empty($sorting)) {
            $sortParams = [];
            foreach ($sorting as $sort) {
                if (isset($sort['id']) && isset($sort['desc'])) {
                    $direction = $sort['desc'] ? 'desc' : 'asc';
                    $searchQuery->orderBy($sort['id'], $direction);
                }
            }
        }

        // Apply tenant-based filtering if required
        if (isset($options['tenantField']) && isset($options['authorizer']) && isset($options['permission'])) {
            $authorizer = $options['authorizer'];
            
            if (!$authorizer->isAllScope && !auth()->user()->isSuperAdmin()) {
                $tenants = $authorizer->getTenants($options['permission'])->pluck('id')->toArray();
                
                if (!empty($tenants)) {
                    // For tenant filtering, use whereIn with the tenant field
                    $searchQuery->whereIn($options['tenantField'], $tenants);
                }
            }
        }

        // Handle the soft delete filter for Typesense
        // Check if the model uses SoftDeletes trait
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($modelClass))) {
            // Check if showDeleted is set and true
            $showDeleted = $request->boolean('showDeleted', false);
            
            if (!$showDeleted) {
                // Only show non-deleted records
                $searchQuery->where('deleted_at', null);
            }
            // If showDeleted is true, we show all records (both deleted and non-deleted)
        }

        // Paginate the results
        return $searchQuery->paginate($request->input('per_page', 15));
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
