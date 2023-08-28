<?php

namespace App\Services;

use App\Enums\AllowedRelationshipablesEnum;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RelationshipService
{
    public static function getModelsByClass(string $modelClass)
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        // get $modelClass delimited by backslash last element
        $modelClassName = Str::upper(array_slice(explode('\\', $modelClass), -1)[0]);

        if (! in_array($modelClassName, AllowedRelationshipablesEnum::toValues())) {
            return [];
        }

        $model = new $modelClass();

        Schema::hasColumn($model->getTable(), 'name')
            ? $models = $modelClass::select('id', 'name')->get()
            : $models = $modelClass::select('id', 'title')->get();

        return new Collection($models);
    }

    public static function getRelatedInstitutionRelations(Institution $institution)
    {
        // first get direct relationships
        $outgoingDirect = $institution->load('outgoingRelationships.pivot.related_model')->outgoingRelationships; // this gets relationshipables which may be figured out
        $incomingDirect = $institution->load('incomingRelationships.pivot.relationshipable')->incomingRelationships; // this gets relationshipables which may be figured out

        // now by type
        $outgoingDirectByType = $institution->load(['types.outgoingRelationships.pivot.related_model.institutions' => function ($query) use ($institution) {
            $query->where('padalinys_id', $institution->padalinys_id);
        }])->types->map(function ($type) {
            return $type->outgoingRelationships;
        })->flatten(1);

        $incomingDirectByType = $institution->load(['types.incomingRelationships.pivot.relationshipable.institutions' => function ($query) use ($institution) {
            $query->where('padalinys_id', $institution->padalinys_id);
        }])->types->map(function ($type) {
            return $type->incomingRelationships;
        })->flatten(1);

        // dd($outgoingDirect, $incomingDirect->pluck('pivot.relationshipable'), $outgoingDirectByType, $incomingDirectByType->pluck('pivot.relationshipable.institutions'));

        return [
            'outgoingDirect' => $outgoingDirect,
            'incomingDirect' => $incomingDirect,
            'outgoingByType' => $outgoingDirectByType,
            'incomingByType' => $incomingDirectByType,
        ];
    }

    public static function getRelatedInstitutions(Institution $institution)
    {
        $relationships = self::getRelatedInstitutionRelations($institution);

        $outgoingDirect = $relationships['outgoingDirect']->pluck('pivot.related_model');
        $incomingDirect = $relationships['incomingDirect']->pluck('pivot.relationshipable');
        $outgoingDirectByType = $relationships['outgoingByType']->pluck('pivot.related_model.institutions')->flatten(1);
        $incomingDirectByType = $relationships['incomingByType']->pluck('pivot.relationshipable.institutions')->flatten(1);

        // return eloquent collection of institutions unique
        return $outgoingDirect->merge($incomingDirect)->merge($outgoingDirectByType)->merge($incomingDirectByType)->unique('id');
    }

    public static function getAllRelatedInstitutions()
    {
        $institutionRelationshipables = collect(Relationshipable::where('relationshipable_type', Institution::class)->get(['relationshipable_id', 'related_model_id'])->toArray()); // OK

        // we need to get all institutions which are related to the institution
        $typeRelationshipables = Relationshipable::where('relationshipable_type', Type::class)->get()->map(function ($relationshipable) {
            return self::getGivenModelsFromModelType(Institution::class, $relationshipable);
        })->flatten(1);

        $institutionRelationships = $institutionRelationshipables->merge($typeRelationshipables);

        return $institutionRelationships;
    }

    /**
     * getGivenModelsFromModelType
     *  Okay so the idea of this is to get all relationships (through relationship givers) from a specific model class, while accounting
     *  for types (this doesn't get direct relationships) and for padalinys. Because when you decide if an e.g. institution is related through
     *  type, you must account for padalinys
     *
     * @param  mixed  $model_type
     * @param  mixed  $relationshipable
     * @return void
     */
    protected static function getGivenModelsFromModelType($model_type, $relationshipable): array
    {
        // The whole function is only for one relationshipable
        $relationships = [];

        // get all giver models. You only need givers to get all relationships, as receivers will duplicate all given relationships
        // but through the other side
        $givers = $model_type::whereHas('types', function ($query) use ($relationshipable) {
            $query->where('types.id', $relationshipable->relationshipable_id);
        })->get();

        // now, for all the givers, find candidates for possible receivers
        $givers->map(function ($giver) use (&$relationships, $model_type, $relationshipable) {
            $giver->receiver = $model_type::whereHas('types', function ($query) use ($relationshipable) {
                $query->where('types.id', $relationshipable->related_model_id);
            })->where('padalinys_id', $giver->padalinys_id)->get()->map(function ($receiver) use ($giver, &$relationships) {
                $relationships[] = [
                    'relationshipable_id' => $giver->id,
                    'related_model_id' => $receiver->id,
                ];
            });
        });

        // This may take a long time to run as it's not eager loaded, need to refactor but not urgent
        return $relationships;
    }
}
