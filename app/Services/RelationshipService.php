<?php

namespace App\Services;

use App\Enums\AllowedRelationshipablesEnum;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RelationshipService
{
    /**
     * Cache TTL for related institutions (1 hour)
     */
    public const CACHE_TTL = 3600;

    /**
     * Get the cache key for an institution's related institutions
     */
    public static function getCacheKey(string $institutionId): string
    {
        return "institution:{$institutionId}:related";
    }

    /**
     * Clear the related institutions cache for a specific institution
     */
    public static function clearRelatedInstitutionsCache(string $institutionId): void
    {
        Cache::forget(self::getCacheKey($institutionId));
    }

    /**
     * Clear all related institutions caches that might be affected by a relationship change.
     * Called when a Relationshipable pivot record is created, updated, or deleted.
     */
    public static function clearCacheForRelationshipable(Relationshipable $relationshipable): void
    {
        // Clear cache for the source institution
        self::clearRelatedInstitutionsCache($relationshipable->relationshipable_id);

        // Clear cache for the target institution
        self::clearRelatedInstitutionsCache($relationshipable->related_model_id);

        // If this is a Type-based relationship, we need to clear all institutions of those types
        if ($relationshipable->relationshipable_type === Type::class) {
            $sourceType = Type::find($relationshipable->relationshipable_id);
            $targetType = Type::find($relationshipable->related_model_id);

            if ($sourceType) {
                $sourceType->institutions()->pluck('id')->each(function ($id) {
                    self::clearRelatedInstitutionsCache($id);
                });
            }
            if ($targetType) {
                $targetType->institutions()->pluck('id')->each(function ($id) {
                    self::clearRelatedInstitutionsCache($id);
                });
            }
        }
    }

    /**
     * Get related institutions for a single institution with caching.
     * Returns a flat collection with metadata about the relationship.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *   institution: Institution,
     *   direction: 'outgoing'|'incoming',
     *   type: 'direct'|'type-based',
     *   source_institution_id: string
     * }>
     */
    public static function getRelatedInstitutionsCached(Institution $institution): \Illuminate\Support\Collection
    {
        return Cache::remember(
            self::getCacheKey($institution->id),
            self::CACHE_TTL,
            fn () => self::getRelatedInstitutionsFlat($institution)
        );
    }

    /**
     * Get related institutions as a flat collection with metadata.
     * This is a cleaner alternative to getRelatedInstitutionRelations that returns
     * institutions directly with relationship context.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *   institution: Institution,
     *   direction: 'outgoing'|'incoming',
     *   type: 'direct'|'type-based',
     *   source_institution_id: string
     * }>
     */
    public static function getRelatedInstitutionsFlat(Institution $institution): \Illuminate\Support\Collection
    {
        $result = collect();
        $sourceId = $institution->id;

        // Direct outgoing relationships
        $outgoingDirect = $institution->load('outgoingRelationships.pivot.related_model')
            ->outgoingRelationships;

        foreach ($outgoingDirect as $relationship) {
            $relatedInstitution = $relationship->pivot->related_model;
            if ($relatedInstitution && $relatedInstitution->id !== $sourceId) {
                $result->push([
                    'institution' => $relatedInstitution,
                    'direction' => 'outgoing',
                    'type' => 'direct',
                    'source_institution_id' => $sourceId,
                ]);
            }
        }

        // Direct incoming relationships
        $incomingDirect = $institution->load('incomingRelationships.pivot.relationshipable')
            ->incomingRelationships;

        foreach ($incomingDirect as $relationship) {
            $relatedInstitution = $relationship->pivot->relationshipable;
            if ($relatedInstitution && $relatedInstitution->id !== $sourceId) {
                $result->push([
                    'institution' => $relatedInstitution,
                    'direction' => 'incoming',
                    'type' => 'direct',
                    'source_institution_id' => $sourceId,
                ]);
            }
        }

        // Type-based outgoing relationships
        // Handle both within-tenant and cross-tenant scopes
        $institution->load(['types.outgoingRelationships.pivot.related_model.institutions.tenant']);

        foreach ($institution->types as $type) {
            foreach ($type->outgoingRelationships as $relationship) {
                $targetType = $relationship->pivot->related_model;
                $scope = $relationship->pivot->scope ?? Relationshipable::SCOPE_WITHIN_TENANT;

                if ($targetType && $targetType->institutions) {
                    foreach ($targetType->institutions as $relatedInstitution) {
                        if ($relatedInstitution->id !== $sourceId) {
                            // Apply scope filtering
                            if (! self::institutionMatchesScope($institution, $relatedInstitution, $scope)) {
                                continue;
                            }

                            $result->push([
                                'institution' => $relatedInstitution,
                                'direction' => 'outgoing',
                                'type' => 'type-based',
                                'source_institution_id' => $sourceId,
                            ]);
                        }
                    }
                }
            }
        }

        // Type-based incoming relationships
        // Handle both within-tenant and cross-tenant scopes
        $institution->load(['types.incomingRelationships.pivot.relationshipable.institutions.tenant']);

        foreach ($institution->types as $type) {
            foreach ($type->incomingRelationships as $relationship) {
                $sourceType = $relationship->pivot->relationshipable;
                $scope = $relationship->pivot->scope ?? Relationshipable::SCOPE_WITHIN_TENANT;

                if ($sourceType && $sourceType->institutions) {
                    foreach ($sourceType->institutions as $relatedInstitution) {
                        if ($relatedInstitution->id !== $sourceId) {
                            // Apply scope filtering
                            if (! self::institutionMatchesScope($institution, $relatedInstitution, $scope)) {
                                continue;
                            }

                            $result->push([
                                'institution' => $relatedInstitution,
                                'direction' => 'incoming',
                                'type' => 'type-based',
                                'source_institution_id' => $sourceId,
                            ]);
                        }
                    }
                }
            }
        }

        // Return unique institutions (keep first occurrence with its metadata)
        return $result->unique(fn ($item) => $item['institution']->id);
    }

    /**
     * Check if a related institution matches the scope criteria for a type-based relationship.
     *
     * @param  Institution  $sourceInstitution  The institution we're finding relations for
     * @param  Institution  $targetInstitution  The potentially related institution
     * @param  string  $scope  The relationship scope ('within-tenant' or 'cross-tenant')
     */
    protected static function institutionMatchesScope(Institution $sourceInstitution, Institution $targetInstitution, string $scope): bool
    {
        if ($scope === Relationshipable::SCOPE_WITHIN_TENANT) {
            // Within-tenant: institutions must be in the same tenant
            return $sourceInstitution->tenant_id === $targetInstitution->tenant_id;
        }

        if ($scope === Relationshipable::SCOPE_CROSS_TENANT) {
            // Cross-tenant: The relationship connects pagrindinis tenant with padalinys-type tenants
            // Source institution should be in pagrindinis, target should be in a padalinys-type tenant
            // OR source is in padalinys-type and target is in pagrindinis
            $sourceTenant = $sourceInstitution->tenant;
            $targetTenant = $targetInstitution->tenant;

            // Load tenant if not loaded
            if (! $sourceTenant) {
                $sourceTenant = Tenant::find($sourceInstitution->tenant_id);
            }
            if (! $targetTenant) {
                $targetTenant = Tenant::find($targetInstitution->tenant_id);
            }

            // Allow if one is pagrindinis and the other is padalinys
            $sourceIsPagrindinis = $sourceTenant?->type === 'pagrindinis';
            $targetIsPagrindinis = $targetTenant?->type === 'pagrindinis';
            $sourceIsPadalinys = $sourceTenant?->type === 'padalinys';
            $targetIsPadalinys = $targetTenant?->type === 'padalinys';

            return ($sourceIsPagrindinis && $targetIsPadalinys)
                || ($sourceIsPadalinys && $targetIsPagrindinis);
        }

        // Unknown scope - default to within-tenant behavior
        return $sourceInstitution->tenant_id === $targetInstitution->tenant_id;
    }

    /**
     * Get related institutions for multiple institutions efficiently.
     * Used for dashboard view where we need related institutions for all user's institutions.
     *
     * @param  Collection<int, Institution>  $institutions
     * @return Collection<int, Institution> Unique related institutions with metadata appended
     */
    public static function getRelatedInstitutionsForMultiple(Collection $institutions): Collection
    {
        $allRelated = collect();
        $institutionIds = $institutions->pluck('id')->toArray();

        foreach ($institutions as $institution) {
            $related = self::getRelatedInstitutionsCached($institution);

            foreach ($related as $item) {
                $relatedInst = $item['institution'];

                // Skip if this institution is already in the user's direct institutions
                if (in_array($relatedInst->id, $institutionIds)) {
                    continue;
                }

                // Skip if we already have this institution
                if ($allRelated->contains('id', $relatedInst->id)) {
                    continue;
                }

                // Add metadata to the institution for frontend use
                $relatedInst->is_related = true;
                $relatedInst->relationship_direction = $item['direction'];
                $relatedInst->relationship_type = $item['type'];
                $relatedInst->source_institution_id = $item['source_institution_id'];

                $allRelated->push($relatedInst);
            }
        }

        // Convert to Eloquent collection and load meetings for Gantt display
        if ($allRelated->isEmpty()) {
            return new Collection();
        }

        // Get IDs and fetch fresh with eager loading
        // Include same relations as DutyService::getInstitutionsForDashboard for Gantt display
        $relatedIds = $allRelated->pluck('id')->toArray();
        $loadedInstitutions = Institution::whereIn('id', $relatedIds)
            ->with([
                'types', // explicit since not auto-loaded
                'meetings:id,title,start_time',
                'meetings.agendaItems:id,meeting_id,title,student_vote,decision,student_benefit',
                'tenant:id,shortname',
                // Load all users (including historical) for Gantt timeline display
                'duties.users',
                'duties.types:id,title,slug',
                'checkIns',
            ])
            ->get();

        // Merge back the metadata
        $metaMap = $allRelated->keyBy('id');
        $loadedInstitutions->each(function ($inst) use ($metaMap) {
            $meta = $metaMap->get($inst->id);
            if ($meta) {
                $inst->is_related = $meta->is_related;
                $inst->relationship_direction = $meta->relationship_direction;
                $inst->relationship_type = $meta->relationship_type;
                $inst->source_institution_id = $meta->source_institution_id;
            }
        });

        return $loadedInstitutions;
    }
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

        $model = new $modelClass;

        Schema::hasColumn($model->getTable(), 'name')
            ? $models = $modelClass::select('id', 'name')->get()
            : $models = $modelClass::select('id', 'title')->get();

        return new Collection($models);
    }

    public static function getRelatedInstitutionRelations(Institution $institution)
    {
        // first get direct relationships
        $outgoingDirect = $institution->load('outgoingRelationships.pivot.related_model.meetings')->outgoingRelationships; // this gets relationshipables which may be figured out
        $incomingDirect = $institution->load('incomingRelationships.pivot.relationshipable.meetings')->incomingRelationships; // this gets relationshipables which may be figured out

        // now by type - load all institutions, scope filtering is applied in the calling code
        $outgoingDirectByType = $institution->load(['types.outgoingRelationships.pivot.related_model.institutions.tenant'])->types->map(function ($type) {
            return $type->outgoingRelationships;
        })->flatten(1);

        $incomingDirectByType = $institution->load(['types.incomingRelationships.pivot.relationshipable.institutions.tenant'])->types->map(function ($type) {
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
        return $outgoingDirect->merge($incomingDirect)->merge((new Collection($outgoingDirectByType))->load('meetings'))->merge((new Collection($incomingDirectByType))->load('meetings'))->unique('id');
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
     *  for types (this doesn't get direct relationships) and for tenant/scope. Because when you decide if an e.g. institution is related through
     *  type, you must account for tenant and scope
     *
     * @param  mixed  $model_type
     * @param  mixed  $relationshipable
     */
    protected static function getGivenModelsFromModelType($model_type, $relationshipable): array
    {
        // The whole function is only for one relationshipable
        $relationships = [];
        $scope = $relationshipable->scope ?? Relationshipable::SCOPE_WITHIN_TENANT;

        // get all giver models. You only need givers to get all relationships, as receivers will duplicate all given relationships
        // but through the other side
        $givers = $model_type::whereHas('types', function ($query) use ($relationshipable) {
            $query->where('types.id', $relationshipable->relationshipable_id);
        })->with('tenant')->get();

        // now, for all the givers, find candidates for possible receivers
        $givers->map(function ($giver) use (&$relationships, $model_type, $relationshipable, $scope) {
            $query = $model_type::whereHas('types', function ($query) use ($relationshipable) {
                $query->where('types.id', $relationshipable->related_model_id);
            })->with('tenant');

            if ($scope === Relationshipable::SCOPE_WITHIN_TENANT) {
                // Within-tenant: only match same tenant
                $query->where('tenant_id', $giver->tenant_id);
            } elseif ($scope === Relationshipable::SCOPE_CROSS_TENANT) {
                // Cross-tenant: match pagrindinis <-> padalinys relationships
                $giverTenant = $giver->tenant;
                if ($giverTenant?->type === 'pagrindinis') {
                    // Giver is in pagrindinis, find receivers in padalinys-type tenants
                    $query->whereHas('tenant', function ($q) {
                        $q->where('type', 'padalinys');
                    });
                } elseif ($giverTenant?->type === 'padalinys') {
                    // Giver is in padalinys, find receivers in pagrindinis tenant
                    $query->whereHas('tenant', function ($q) {
                        $q->where('type', 'pagrindinis');
                    });
                } else {
                    // Other tenant types - fall back to within-tenant behavior
                    $query->where('tenant_id', $giver->tenant_id);
                }
            }

            $query->get()->each(function ($receiver) use ($giver, &$relationships) {
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
