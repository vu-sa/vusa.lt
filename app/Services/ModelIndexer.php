<?php

namespace App\Services;

use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ModelIndexer
{
    // check argument to be class of model
    public function execute(string $modelClass, ?string $search, string $searchable, Authorizer $authorizer, ?bool $hasManyPadalinys = true): Builder
    {
        if (! class_exists($modelClass)) {
            // return exception that the class doesn't exist
            new Exception('Class '.$modelClass.' does not exist');
        }

        $user = User::find((Auth::id()));

        $padalinysRelationString = $hasManyPadalinys ? 'padaliniai' : 'padalinys';

        // first need to check if has permission to view all models

        // TODO: get only needed data for index
        if ($authorizer->isAllScope || $user->hasRole(config('permission.super_admin_role_name'))) {
            return $modelClass::with($padalinysRelationString)->where($searchable, 'like', "%{$search}%");
        }

        $modelsBuilder = $modelClass::whereHas($padalinysRelationString, function (Builder $query) use ($authorizer, $hasManyPadalinys) {
            $query->whereIn(optional($hasManyPadalinys, fn () => 'padaliniai.').'id', $authorizer->getPadaliniai()->pluck('id'));
        })->where($searchable, 'like', "%{$search}%");

        return $modelsBuilder;
    }

    // TODO: implement this method in all models (and make ModelIndexer non static)
    public static function filterByAuthorized(\Laravel\Scout\Builder $builder, Authorizer $authorizer, ?bool $hasManyPadalinys = true)
    {
        $user = User::query()->find((Auth::id()));

        $padalinysRelationString = $hasManyPadalinys ? 'padaliniai' : 'padalinys';

        // first need to check if has permission to view all models

        // TODO: get only needed data for index
        if ($authorizer->isAllScope || $user->hasRole(config('permission.super_admin_role_name'))) {
            return $builder->query(fn (Builder $query) => $query->with($padalinysRelationString));
        }

        return $builder->query(fn (Builder $query) => $query->whereHas($padalinysRelationString, function (Builder $query) use ($authorizer, $hasManyPadalinys) {
            $query->whereIn(optional($hasManyPadalinys, fn () => 'padaliniai.').'id', $authorizer->getPadaliniai()->pluck('id'));
        }));
    }

    public static function filterByColumn(\Laravel\Scout\Builder $builder, string $column, ?array $filters): \Laravel\Scout\Builder
    {
        return $builder->when(isset(
            $filters[$column]
        ) && $filters[$column] !== [], function ($query) use ($filters, $column) {
            $query->whereIn($column, $filters[$column]);
        });
    }
}
