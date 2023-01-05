<?php

namespace App\Services;

use App\Models\Relationship;
use App\Models\Type;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Collection;
use App\Enums\AllowedRelationshipablesEnum;

class RelationshipService
{
    public static function getModelsByClass(string $modelClass)
    {
        if (!class_exists($modelClass)) {
            return [];
        }

        // get $modelClass delimited by backslash last element
        $modelClassName = array_slice(explode('\\', $modelClass), -1)[0];

        if (!in_array($modelClassName, AllowedRelationshipablesEnum::toValues())) {
            return [];
        }

        $model = new $modelClass();

        Schema::hasColumn($model->getTable(), 'name')
            ? $models = $modelClass::select('id', 'name')->get()
            : $models = $modelClass::select('id', 'title')->get();

        return new Collection($models);
    }
}