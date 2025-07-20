<?php

namespace App\Services;

use App\Models\Model;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Scout\Builder;

class ModelIndexer
{
    public Builder $builder;

    // Usually using Model::class
    private $indexable;

    private ?string $search;

    private ?array $sorting;

    public ?array $filters;

    private array $callbacksArray = [];

    private string $tenantRelationString;

    public Authorizer $authorizer;

    /**
     * Create a new ModelIndexer instance
     *
     * @param  mixed  $indexable  The model class to index
     */
    public function __construct($indexable)
    {
        $request = request();
        $this->indexable = $indexable;
        $this->search = $request->input('text');

        // Process sorting - accept either JSON or base64 encoded JSON for backward compatibility
        $sortingInput = $request->input('sorting');
        if ($sortingInput) {
            if (Str::startsWith($sortingInput, '{')) {
                // JSON format
                $this->sorting = json_decode($sortingInput, true);
            } else {
                // Base64 encoded (legacy format)
                $this->sorting = json_decode(base64_decode($sortingInput), true);
            }
        } else {
            $this->sorting = null; // Corrected from $this->sorters to $this->sorting
        }

        // Process filters - accept either JSON or base64 encoded JSON for backward compatibility
        $filtersInput = $request->input('filters');
        if ($filtersInput) {
            if (Str::startsWith($filtersInput, '{')) {
                // JSON format
                $this->filters = json_decode($filtersInput, true);
            } else {
                // Base64 encoded (legacy format)
                $this->filters = json_decode(base64_decode($filtersInput), true);
            }

            // Validate filter values are arrays
            if ($this->filters) {
                foreach ($this->filters as $key => $value) {
                    if (! is_array($value)) {
                        $this->filters[$key] = [$value];
                    }
                }
            }
        } else {
            $this->filters = null;
        }

        // get authorizer singleton
        $this->authorizer = app(Authorizer::class);

        $this->tenantRelationString = $indexable->whichUnitRelation();

        $this->search();
        $this->setEloquentQuery();
        $this->takeCareOfRelationFilters();

        // check if model uses SoftDeletes trait
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->indexable))) {
            $this->onlyTrashed($request);
        }
    }

    /**
     * Initialize the search with the indexable model
     * For admin operations, use database driver to avoid circular dependencies
     *
     * @return $this
     */
    public function search()
    {
        // Store the original Scout driver
        $originalDriver = config('scout.driver');
        
        // Use database driver for admin searches to prevent circular dependencies
        // during indexing operations
        config(['scout.driver' => 'database']);
        
        try {
            $this->builder = $this->indexable::search($this->search);
        } finally {
            // Always restore the original driver
            config(['scout.driver' => $originalDriver]);
        }

        return $this;
    }

    /**
     * Set up the Eloquent query closure for the search
     *
     * @param  array  $callbacks  Additional callbacks to apply to the query
     * @param  bool  $authorize  Whether to apply authorization constraints
     * @return $this
     */
    public function setEloquentQuery($callbacks = [], $authorize = true)
    {
        $eloquentQueryClosure = function (EloquentBuilder $query) use ($callbacks, $authorize) {
            // Add $callbacks to $this->callbacksArray
            $this->callbacksArray = array_merge($this->callbacksArray, $callbacks);

            $query->with($this->tenantRelationString);

            if ($authorize) {
                // Add authorizer closure to callbacks as the first element
                array_unshift($this->callbacksArray, $this->authorizerClosure());
            }

            foreach ($this->callbacksArray as $callback) {
                $callback($query);
            }
        };

        $this->builder->query($eloquentQueryClosure);

        return $this;
    }

    /**
     * Process relation filters (fields containing dots)
     *
     * @return $this
     */
    public function takeCareOfRelationFilters()
    {
        // Check if filters keys have dots, extract them and remove from filters
        if (! $this->filters) {
            $this->filters = [];
        }

        $relationFilters = array_filter($this->filters, fn ($key) => Str::contains($key, '.'), ARRAY_FILTER_USE_KEY);
        $this->filters = array_diff_key($this->filters, $relationFilters);

        // For each relation filter, create a callback that is added to callbacksArray
        foreach ($relationFilters as $relationFilterKey => $relationFilterValue) {
            $relationFilterKeyArray = explode('.', $relationFilterKey);

            $relationFilterCallback = function (EloquentBuilder $query) use ($relationFilterKeyArray, $relationFilterValue, $relationFilterKey) {
                $query->when(! in_array($relationFilterValue, [[], null]),
                    fn (EloquentBuilder $query) => $query->whereHas(
                        $relationFilterKeyArray[0],
                        fn (EloquentBuilder $query) => $query->whereIn(
                            // Sometimes some variables may be described as ambiguous, so we need to specify which id we want to use
                            $relationFilterKey !== 'tenants.id' ? $relationFilterKeyArray[1] : $relationFilterKey,
                            $relationFilterValue
                        )
                    )
                );
            };

            array_push($this->callbacksArray, $relationFilterCallback);
        }

        return $this;
    }

    /**
     * Create an authorizer closure to restrict access based on user permissions
     *
     * @return \Closure
     */
    private function authorizerClosure()
    {
        $user = User::query()->find((Auth::id()));

        // Derive the appropriate permission based on model being indexed
        $modelName = Str::plural(Str::camel(class_basename($this->indexable)));
        $permission = "{$modelName}.read.padalinys";

        return fn (EloquentBuilder $query) => $query->when(
            ! $this->authorizer->isAllScope && ! $user->isSuperAdmin(),
            fn (EloquentBuilder $query) => $query->whereHas(
                $this->tenantRelationString,
                fn (EloquentBuilder $query) => $query->whereIn(
                    // Optional, because this is how relationship is queried in query builder
                    optional($this->tenantRelationString === 'tenants', fn () => 'tenants.id'),
                    $this->authorizer->getTenants($permission)->pluck('id')->toArray()
                )
            )
        );
    }

    /**
     * Apply direct column filters from the filter parameter
     *
     * @return $this
     */
    public function filterAllColumns()
    {
        if (! $this->filters) {
            return $this;
        }

        foreach ($this->filters as $name => $value) {
            if (! is_array($value)) {
                $value = [$value];
            }

            $this->builder->when(
                // When not empty, filter
                $value !== [],
                function (Builder $query) use ($name, $value) {
                    $query->whereIn($name, $value);
                }
            );
        }

        return $this;
    }

    /**
     * Handle soft-deleted records filter
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    protected function onlyTrashed($request)
    {
        if ($request->input('showSoftDeleted') === 'true') {
            $this->builder->onlyTrashed();
        }

        return $this;
    }

    /**
     * Apply sorting based on the sorting parameter
     *
     * @param  array|null  $default  Default sort order if none specified
     * @return $this
     */
    public function sortAllColumns(?array $default = null)
    {
        if ($default && ! $this->sorting) {
            $this->sorting = $default;
        }

        if (! $this->sorting) {
            return $this;
        }

        foreach ($this->sorting as $name => $value) {
            if ($value) {
                $this->builder->orderBy($name, $value === 'descend' ? 'desc' : 'asc');
            }
        }

        return $this;
    }
}
