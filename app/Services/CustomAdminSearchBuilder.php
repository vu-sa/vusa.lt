<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Custom search builder for admin searches that bypasses Scout's shouldBeSearchable filtering
 * This allows admin users to search ALL records regardless of publication status
 */
class CustomAdminSearchBuilder
{
    protected $model;

    protected $search;

    protected $queryCallback;

    private array $filters = [];

    private array $ordering = [];

    public function __construct(string $modelClass, string $search = '')
    {
        $this->model = $modelClass;
        $this->search = $search;
    }

    /**
     * Set a query callback (compatible with Scout Builder interface)
     */
    public function query(callable $callback)
    {
        $this->queryCallback = $callback;

        return $this;
    }

    /**
     * Execute the search and return results
     */
    public function get(): Collection
    {
        $query = $this->model::query();

        // Apply search logic based on model type
        if (! empty($this->search)) {
            $this->applySearchConstraints($query);
        }

        // Apply stored filters
        $this->applyFilters($query);

        // Apply stored ordering
        $this->applyOrdering($query);

        // Apply the callback if set (for filters, authorization, etc.)
        if ($this->queryCallback) {
            call_user_func($this->queryCallback, $query);
        }

        return $query->get();
    }

    /**
     * Add model-specific search constraints
     */
    private function applySearchConstraints(EloquentBuilder $query): void
    {
        $searchTerm = '%'.$this->search.'%';

        switch ($this->model) {
            case 'App\Models\News':
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', $searchTerm)
                        ->orWhere('short', 'like', $searchTerm)
                        ->orWhere('permalink', 'like', $searchTerm);
                });
                break;

            case 'App\Models\Page':
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', $searchTerm)
                        ->orWhere('permalink', 'like', $searchTerm);
                });
                break;

            case 'App\Models\Calendar':
                $query->where(function ($q) use ($searchTerm) {
                    // Search in translatable title fields
                    $q->where('title->lt', 'like', $searchTerm)
                        ->orWhere('title->en', 'like', $searchTerm)
                        ->orWhere('description->lt', 'like', $searchTerm)
                        ->orWhere('description->en', 'like', $searchTerm)
                        ->orWhere('location->lt', 'like', $searchTerm)
                        ->orWhere('location->en', 'like', $searchTerm);
                });
                break;

            case 'App\Models\Document':
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('title', 'like', $searchTerm)
                        ->orWhere('summary', 'like', $searchTerm);
                });
                break;

            default:
                // For other models, try common fields
                $query->where(function ($q) use ($searchTerm) {
                    if ($this->hasColumn('title')) {
                        $q->where('title', 'like', $searchTerm);
                    }
                    if ($this->hasColumn('name')) {
                        $q->orWhere('name', 'like', $searchTerm);
                    }
                    if ($this->hasColumn('description')) {
                        $q->orWhere('description', 'like', $searchTerm);
                    }
                });
                break;
        }
    }

    /**
     * Check if the model has a specific column
     */
    private function hasColumn(string $column): bool
    {
        $instance = new $this->model;

        return $instance->getConnection()
            ->getSchemaBuilder()
            ->hasColumn($instance->getTable(), $column);
    }

    /**
     * Paginate results (compatible with Scout Builder interface)
     */
    public function paginate($perPage = 15, $pageName = 'page', $page = null)
    {
        $query = $this->model::query();

        // Apply search logic
        if (! empty($this->search)) {
            $this->applySearchConstraints($query);
        }

        // Apply stored filters
        $this->applyFilters($query);

        // Apply stored ordering
        $this->applyOrdering($query);

        // Apply the callback if set
        if ($this->queryCallback) {
            call_user_func($this->queryCallback, $query);
        }

        return $query->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Count results
     */
    public function count(): int
    {
        $query = $this->model::query();

        // Apply search logic
        if (! empty($this->search)) {
            $this->applySearchConstraints($query);
        }

        // Apply stored filters
        $this->applyFilters($query);

        // Apply stored ordering
        $this->applyOrdering($query);

        // Apply the callback if set
        if ($this->queryCallback) {
            call_user_func($this->queryCallback, $query);
        }

        return $query->count();
    }

    /**
     * Add ordering (compatible with Scout Builder interface)
     */
    public function orderBy(string $column, string $direction = 'asc')
    {
        // Store the ordering instruction for later application
        $this->ordering[] = [$column, $direction];

        return $this;
    }

    /**
     * Add where clauses for filters (compatible with Scout Builder interface)
     */
    public function whereIn(string $field, array $values)
    {
        // Store the filter to be applied during query execution
        $this->filters[$field] = $values;

        return $this;
    }

    /**
     * Apply stored filters to the query
     */
    private function applyFilters(EloquentBuilder $query): void
    {
        foreach ($this->filters as $field => $values) {
            if (! empty($values)) {
                $query->whereIn($field, $values);
            }
        }
    }

    /**
     * Apply stored ordering to the query
     */
    private function applyOrdering(EloquentBuilder $query): void
    {
        foreach ($this->ordering as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }
    }

    /**
     * Handle only trashed records (compatible with Scout Builder interface)
     */
    public function onlyTrashed()
    {
        // This will be handled by the query callback in the actual implementation
        return $this;
    }

    /**
     * Apply conditional logic (compatible with Scout Builder interface)
     */
    public function when($value, callable $callback, ?callable $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }
}
