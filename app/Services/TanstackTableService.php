<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TanstackTableService
{
    /**
     * Apply sorting to a query builder from Tanstack Table sorting format
     *
     * @param  Builder  $query  The query builder to apply sorting to
     * @param  array  $sorting  Tanstack Table format sorting array: [{"id":"name","desc":true}]
     */
    public function applySorting(Builder $query, array $sorting): Builder
    {
        if (empty($sorting)) {
            return $query;
        }

        // Track added joins to avoid duplicates
        $addedJoins = [];

        foreach ($sorting as $sort) {
            if (isset($sort['id']) && isset($sort['desc'])) {
                // Check if it's a direct column or a relationship column
                if (str_contains($sort['id'], '.')) {
                    // Handle relationship sort with proper join
                    [$relation, $column] = explode('.', $sort['id'], 2);
                    $model = $query->getModel();

                    // Make sure the relationship exists
                    if (method_exists($model, $relation)) {
                        $relationObj = $model->{$relation}();
                        $relatedTable = $relationObj->getRelated()->getTable();
                        $foreignKey = $relationObj->getForeignKeyName();
                        $localKey = $relationObj->getLocalKeyName() ?: 'id';

                        // Generate a unique join name to avoid conflicts
                        $joinName = "{$relatedTable}_sort";

                        // Only add join if not already added
                        if (! in_array($joinName, $addedJoins)) {
                            $query->leftJoin(
                                "{$relatedTable} as {$joinName}",
                                "{$joinName}.{$localKey}",
                                '=',
                                "{$model->getTable()}.{$foreignKey}"
                            );
                            $addedJoins[] = $joinName;
                        }

                        // Apply sorting
                        $query->orderBy("{$joinName}.{$column}", $sort['desc'] ? 'desc' : 'asc');
                    }
                } else {
                    // Direct column sort
                    $query->orderBy($sort['id'], $sort['desc'] ? 'desc' : 'asc');
                }
            }
        }

        return $query;
    }

    /**
     * Apply filtering to a query builder from Tanstack Table filter format
     *
     * @param  Builder  $query  The query builder to apply filters to
     * @param  array  $filters  Tanstack Table format filters object: {"type.id":[1,2],"status":["active"]}
     */
    public function applyFiltering(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            // Skip empty filters
            if ($value === null || (is_array($value) && empty($value))) {
                continue;
            }

            $this->applyFilter($query, $key, $value);
        }

        return $query;
    }

    /**
     * Apply global search to a query builder
     *
     * @param  Builder  $query  The query builder to apply search to
     * @param  string|null  $searchText  The text to search for
     * @param  array  $searchableColumns  The columns to search in
     */
    public function applyGlobalSearch(Builder $query, ?string $searchText, array $searchableColumns = []): Builder
    {
        if (empty($searchText) || empty($searchableColumns)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($searchText, $searchableColumns) {
            foreach ($searchableColumns as $column) {
                if (Str::contains($column, '.')) {
                    // Handle relationship columns
                    $parts = explode('.', $column, 2);
                    $relation = $parts[0];
                    $relatedColumn = $parts[1];

                    $q->orWhereHas($relation, function (Builder $subQ) use ($relatedColumn, $searchText) {
                        $subQ->where($relatedColumn, 'like', "%{$searchText}%");
                    });
                } else {
                    // Handle direct columns
                    $q->orWhere($column, 'like', "%{$searchText}%");
                }
            }
        });
    }

    /**
     * Apply tenant-based access restrictions to a query
     *
     * @param  Builder  $query  The query builder
     * @param  string  $tenantRelation  The relationship name pointing to the tenant
     * @param  string  $permission  The permission to check
     * @param  ModelAuthorizer  $authorizer  The authorizer service
     */
    public function applyTenantRestrictions(
        Builder $query,
        string $tenantRelation,
        string $permission,
        ModelAuthorizer $authorizer
    ): Builder {
        $user = User::find(Auth::id());

        if ($authorizer->isAllScope || $user->isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas($tenantRelation, function (Builder $q) use ($tenantRelation, $permission, $authorizer) {
            $columnName = $tenantRelation === 'tenants' ? 'tenants.id' : 'id';
            $q->whereIn($columnName, $authorizer->getTenants($permission)->pluck('id'));
        });
    }

    /**
     * Apply soft delete filters to query
     *
     * @param  Builder  $query  The query builder
     * @param  bool  $showDeleted  Whether to include soft-deleted records
     */
    public function applySoftDeleteFilter(Builder $query, bool $showDeleted = false): Builder
    {
        $model = $query->getModel();

        // Check if model uses SoftDeletes trait
        if (! in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($model))) {
            return $query;
        }

        if ($showDeleted) {
            return $query->withTrashed();
        }

        return $query;
    }

    /**
     * Apply permission-based filtering using the ModelAuthorizer
     *
     * @param  Builder  $query  The query builder
     * @param  string  $tenantRelation  The relation name that connects to the tenant
     * @param  string  $permission  The permission string to check
     * @param  ModelAuthorizer  $authorizer  The authorizer service
     */
    public function applyPermissionFiltering(
        Builder $query,
        string $tenantRelation,
        string $permission,
        ModelAuthorizer $authorizer
    ): Builder {
        // Only apply if not all scope and not super admin
        if (! $authorizer->isAllScope && ! auth()->user()->isSuperAdmin()) {
            return $query->whereHas($tenantRelation, function (Builder $q) use ($tenantRelation, $permission, $authorizer) {
                $columnName = $tenantRelation === 'tenants' ? 'tenants.id' : 'id';
                $q->whereIn($columnName, $authorizer->getTenants($permission)->pluck('id'));
            });
        }

        return $query;
    }

    /**
     * Apply a specific filter to the query
     */
    protected function applyFilter(Builder $query, string $key, mixed $value): void
    {
        $modelTable = $query->getModel()->getTable();

        // Handle relationship filters (e.g., 'tenant.id')
        if (Str::contains($key, '.')) {
            $parts = explode('.', $key, 2);
            $relation = $parts[0];
            $column = $parts[1];

            // Check if relation exists on model
            if (method_exists($query->getModel(), $relation)) {
                $query->whereHas($relation, function (Builder $q) use ($column, $value) {
                    if (is_array($value)) {
                        $q->whereIn($column, $value);
                    } else {
                        $q->where($column, $value);
                    }
                });
            }

            return;
        }

        // Handle direct column filters
        if (Schema::hasColumn($modelTable, $key)) {
            if (is_array($value)) {
                // Handle array values (multi-select)
                $query->whereIn($key, $value);
            } elseif (is_string($value)) {
                // Handle string values (text search)
                $query->where($key, 'like', "%{$value}%");
            } elseif ($value instanceof \DateTimeInterface) {
                // Handle date values
                $query->whereDate($key, $value);
            } elseif (is_bool($value)) {
                // Handle boolean values
                $query->where($key, $value);
            } else {
                // Default equality check
                $query->where($key, $value);
            }
        }
    }

    /**
     * Map a frontend sort column ID to a database column
     */
    protected function mapSortColumn($model, string $key): ?string
    {
        $modelTable = $model->getTable();

        // Allow sorting by actual table columns
        if (Schema::hasColumn($modelTable, $key)) {
            return $key;
        }

        // Handle relationship sorts (e.g., tenant.name)
        if (Str::contains($key, '.')) {
            $parts = explode('.', $key, 2);
            $relation = $parts[0];
            $column = $parts[1];

            // Check if relation exists
            if (method_exists($model, $relation)) {
                // For proper relationship sorting, you would need to join the related table
                // This is a simplified example - you might want to implement more complex logic
                $relationObj = $model->{$relation}();
                $relatedTable = $relationObj->getRelated()->getTable();

                return "{$relatedTable}.{$column}";
            }
        }

        return null;
    }
}
