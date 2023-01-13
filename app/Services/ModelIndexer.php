<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Services\ModelAuthorizer as Authorizer;

class ModelIndexer {
    
    // check argument to be class of model
    public function execute(string $modelClass, string | null $search, string $searchable, Authorizer $authorizer, bool | null $hasManyPadalinys = true): Builder
    {
        if (!class_exists($modelClass)) {
            // return exception that the class doesn't exist
            return new Exception('Class ' . $modelClass . ' does not exist');
        }
        
        $user = User::find((Auth::id()));
        $padalinysRelationString = $hasManyPadalinys ? 'padaliniai' : 'padalinys';
        
        // first need to check if has permission to view all models

        // TODO: get only needed data for index
        if ($authorizer->isAllScope || $user->hasRole(config('permission.super_admin_role_name'))) {
            return $modelClass::with($padalinysRelationString)->where($searchable, 'like', "%{$search}%");
        }

        $modelsBuilder = $modelClass::whereHas($padalinysRelationString, function (Builder $query) use ($authorizer, $hasManyPadalinys) {
            $query->whereIn(optional($hasManyPadalinys, fn () => 'padaliniai.') . 'id', $authorizer->getPadaliniai()->pluck('id'));
        })->where($searchable, 'like', "%{$search}%");

        return $modelsBuilder;
    }
}