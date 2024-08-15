<?php

namespace App\Services;

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

    private ?array $sorters;

    public ?array $filters;

    private array $callbacksArray = [];

    private Authorizer $authorizer;

    private string $tenantRelationString;

    public function __construct($indexable, $request, $authorizer)
    {
        $this->indexable = $indexable;
        $this->search = $request->input('text');
        $this->sorters = json_decode(base64_decode($request->input('sorters')), true);
        $this->filters = json_decode(base64_decode($request->input('filters')), true);

        $this->authorizer = $authorizer;

        $this->tenantRelationString = $indexable->whichUnitRelation();

        $this->search();
        $this->setEloquentQuery();
        $this->takeCareOfRelationFilters();

        // check if model uses SoftDeletes trait
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->indexable))) {
            $this->onlyTrashed($request);
        }
    }

    public function search()
    {
        // check if indexable implements Searchable trait
        if (! in_array('Laravel\Scout\Searchable', class_uses($this->indexable))) {
            $this->builder = $this->indexable::search($this->search);
        } else {
            $this->builder = $this->indexable::query();
        }

        return $this;
    }

    // This is needed because Laravel\Scout\Builder can support only one query
    public function setEloquentQuery($callbacks = [], $authorize = true)
    {
        $eloquentQueryClosure = function (EloquentBuilder $query) use ($callbacks, $authorize) {

            // add $callbacks to $this->callbacksArray
            $this->callbacksArray = array_merge($this->callbacksArray, $callbacks);

            $query->with($this->tenantRelationString);

            if ($authorize) {
                // add authorizer closure to callbacks to the first element of the callbacks
                array_unshift($this->callbacksArray, $this->authorizerClosure());
            }

            foreach ($this->callbacksArray as $callback) {
                $callback($query);
            }
        };

        $this->builder->query($eloquentQueryClosure);

        return $this;
    }

    public function takeCareOfRelationFilters()
    {
        // check if filters keys have dots, get them and remove from filters

        if (! $this->filters) {
            $this->filters = [];
        }

        $relationFilters = array_filter($this->filters, fn ($key) => Str::contains($key, '.'), ARRAY_FILTER_USE_KEY);

        $this->filters = array_diff_key($this->filters, $relationFilters);

        // foreach relation filters, create a callback that is added to callbacksArray
        foreach ($relationFilters as $relationFilterKey => $relationFilterValue) {

            $relationFilterKeyArray = explode('.', $relationFilterKey);

            $relationFilterCallback = function (EloquentBuilder $query) use ($relationFilterKeyArray, $relationFilterValue, $relationFilterKey) {
                $query->when(! in_array($relationFilterValue, [[], null]),
                    fn (EloquentBuilder $query) => $query->whereHas(
                        $relationFilterKeyArray[0], fn (EloquentBuilder $query) => $query->whereIn(
                            // Sometimes some variables may be described as ambiguous, so we need to specify, which id we want to use
                            // TODO: use it the same way as in authorizer
                            $relationFilterKey !== 'tenants.id' ? $relationFilterKeyArray[1] : $relationFilterKey, $relationFilterValue)
                    )
                );
            };

            array_push($this->callbacksArray, $relationFilterCallback);
        }

        return $this;
    }

    private function authorizerClosure()
    {
        $user = User::query()->find((Auth::id()));

        return fn (EloquentBuilder $query) => $query->when(
            ! $this->authorizer->isAllScope && ! $user->hasRole(config('permission.super_admin_role_name')),
            fn (EloquentBuilder $query) => $query->whereHas(
                $this->tenantRelationString, fn (EloquentBuilder $query) => $query->whereIn(
                    // Optional, because this is how relationship is queried in query builder
                    optional($this->tenantRelationString === 'tenants', fn () => 'tenants.id'), $this->authorizer->getTenants()->pluck('id')->toArray())
            )
        );
    }

    public function filterAllColumns()
    {
        if (! $this->filters) {
            return $this;
        }

        foreach ($this->filters as $name => $value) {
            $this->builder->when(
                //# When is not empty, filter
                $value !== [],
                function (Builder $query) use ($name, $value) {
                    $query->whereIn($name, $value);
                });
        }

        return $this;
    }

    protected function onlyTrashed($request)
    {
        if ($request->input('showSoftDeleted') === 'true') {
            $this->builder->onlyTrashed();
        }

        return $this;
    }

    public function sortAllColumns(?array $default = null)
    {
        if ($default && ! $this->sorters) {
            $this->sorters = $default;
        }

        if (! $this->sorters) {
            return $this;
        }

        foreach ($this->sorters as $name => $value) {
            $this->builder->when($value, function (Builder $query) use ($name, $value) {
                $query->orderBy($name, $value === 'descend' ? 'desc' : 'asc');
            });
        }

        return $this;
    }
}
