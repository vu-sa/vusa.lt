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
     *   direction: 'outgoing'|'incoming'|'sibling',
     *   type: 'direct'|'type-based'|'within-type',
     *   source_institution_id: string,
     *   authorized: bool
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
     * The 'authorized' property indicates whether the source institution has data access
     * to the related institution:
     * - outgoing: true (source can see target's data)
     * - sibling: true (mutual visibility)
     * - incoming: false (target can see source, but source cannot see target)
     *
     * @return \Illuminate\Support\Collection<int, array{
     *   institution: Institution,
     *   direction: 'outgoing'|'incoming'|'sibling',
     *   type: 'direct'|'type-based'|'within-type',
     *   source_institution_id: string,
     *   authorized: bool
     * }>
     */
    public static function getRelatedInstitutionsFlat(Institution $institution): \Illuminate\Support\Collection
    {
        $result = collect();
        $sourceId = $institution->id;

        // Ensure source institution has tenant loaded for scope matching
        if (! $institution->relationLoaded('tenant')) {
            $institution->load('tenant');
        }

        // Ensure types are loaded for sibling relationship checks
        if (! $institution->relationLoaded('types')) {
            $institution->load('types');
        }

        // Direct outgoing relationships
        $outgoingDirect = $institution->load('outgoingRelationships.pivot.related_model')
            ->outgoingRelationships;

        foreach ($outgoingDirect as $relationship) {
            /** @var Relationshipable $pivot */
            $pivot = $relationship->getRelation('pivot');
            /** @var Institution|null $relatedInstitution */
            $relatedInstitution = $pivot->related_model;
            if ($relatedInstitution && $relatedInstitution->id !== $sourceId) {
                $result->push([
                    'institution' => $relatedInstitution,
                    'direction' => 'outgoing',
                    'type' => 'direct',
                    'source_institution_id' => $sourceId,
                    'authorized' => true,
                ]);
            }
        }

        // Direct incoming relationships
        $incomingDirect = $institution->load('incomingRelationships.pivot.relationshipable')
            ->incomingRelationships;

        foreach ($incomingDirect as $relationship) {
            /** @var Relationshipable $pivot */
            $pivot = $relationship->getRelation('pivot');
            /** @var Institution|null $relatedInstitution */
            $relatedInstitution = $pivot->relationshipable;
            if ($relatedInstitution && $relatedInstitution->id !== $sourceId) {
                // Check if the relationship is bidirectional - if so, incoming also gets authorization
                $isBidirectional = $pivot->bidirectional ?? false;

                $result->push([
                    'institution' => $relatedInstitution,
                    'direction' => 'incoming',
                    'type' => 'direct',
                    'source_institution_id' => $sourceId,
                    'authorized' => $isBidirectional,
                ]);
            }
        }

        // Type-based outgoing relationships
        // Handle both within-tenant and cross-tenant scopes
        $institution->load(['types.outgoingRelationships.pivot.related_model.institutions.tenant']);

        foreach ($institution->types as $type) {
            foreach ($type->outgoingRelationships as $relationship) {
                /** @var Relationshipable $pivot */
                $pivot = $relationship->getRelation('pivot');
                /** @var Type|null $targetType */
                $targetType = $pivot->related_model;
                $scope = $pivot->scope ?? Relationshipable::SCOPE_WITHIN_TENANT;
                $isBidirectional = $pivot->bidirectional ?? false;
                $authorized = self::isTypeRelationshipAuthorized($institution, $scope, $isBidirectional, 'outgoing');

                if ($targetType && $targetType->institutions->isNotEmpty()) {
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
                                'authorized' => $authorized,
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
                /** @var Relationshipable $pivot */
                $pivot = $relationship->getRelation('pivot');
                /** @var Type|null $sourceType */
                $sourceType = $pivot->relationshipable;
                $scope = $pivot->scope ?? Relationshipable::SCOPE_WITHIN_TENANT;
                $isBidirectional = $pivot->bidirectional ?? false;
                $authorized = self::isTypeRelationshipAuthorized($institution, $scope, $isBidirectional, 'incoming');

                if ($sourceType && $sourceType->institutions->isNotEmpty()) {
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
                                'authorized' => $authorized,
                            ]);
                        }
                    }
                }
            }
        }

        // Within-type sibling relationships (same type + same tenant)
        // Only for types that have enable_sibling_relationships flag set in extra_attributes
        foreach ($institution->types as $type) {
            if (! $type->hasSiblingRelationshipsEnabled()) {
                continue;
            }

            // Get sibling institutions: same type, same tenant, different institution
            $siblingInstitutions = Institution::query()
                ->where('tenant_id', $institution->tenant_id)
                ->where('id', '!=', $sourceId)
                ->whereHas('types', fn ($q) => $q->where('types.id', $type->id))
                ->with('tenant')
                ->get();

            foreach ($siblingInstitutions as $siblingInstitution) {
                $result->push([
                    'institution' => $siblingInstitution,
                    'direction' => 'sibling',
                    'type' => 'within-type',
                    'source_institution_id' => $sourceId,
                    'authorized' => true,
                ]);
            }
        }

        // Cross-tenant sibling relationships (pagrindinis <-> padalinys)
        // Only for types that have enable_cross_tenant_sibling_relationships flag set in extra_attributes
        // Authorization is one-directional:
        // - pagrindinis institutions see padalinys siblings with authorized: true
        // - padalinys institutions see pagrindinis sibling with authorized: false (visible but no data access)
        $institutionTenant = $institution->tenant;
        if (! $institutionTenant || $institutionTenant->type === null || $institutionTenant->type === '') {
            $institutionTenant = Tenant::find($institution->tenant_id);
        }

        foreach ($institution->types as $type) {
            if (! $type->hasCrossTenantSiblingRelationshipsEnabled()) {
                continue;
            }

            if ($institutionTenant?->type === 'pagrindinis') {
                // Pagrindinis institution sees padalinys siblings with full authorization
                $crossTenantSiblings = Institution::query()
                    ->where('id', '!=', $sourceId)
                    ->whereHas('tenant', fn ($q) => $q->where('type', 'padalinys'))
                    ->whereHas('types', fn ($q) => $q->where('types.id', $type->id))
                    ->with('tenant')
                    ->get();

                foreach ($crossTenantSiblings as $siblingInstitution) {
                    $result->push([
                        'institution' => $siblingInstitution,
                        'direction' => 'sibling',
                        'type' => 'cross-tenant-sibling',
                        'source_institution_id' => $sourceId,
                        'authorized' => true,
                    ]);
                }
            } elseif ($institutionTenant?->type === 'padalinys') {
                // Padalinys institution sees pagrindinis sibling but without authorization
                // (can see meetings exist, but no agenda items access)
                $crossTenantSiblings = Institution::query()
                    ->where('id', '!=', $sourceId)
                    ->whereHas('tenant', fn ($q) => $q->where('type', 'pagrindinis'))
                    ->whereHas('types', fn ($q) => $q->where('types.id', $type->id))
                    ->with('tenant')
                    ->get();

                foreach ($crossTenantSiblings as $siblingInstitution) {
                    $result->push([
                        'institution' => $siblingInstitution,
                        'direction' => 'sibling',
                        'type' => 'cross-tenant-sibling',
                        'source_institution_id' => $sourceId,
                        'authorized' => false,
                    ]);
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
            $sourceTenantType = self::getTenantType($sourceInstitution);
            $targetTenantType = self::getTenantType($targetInstitution);

            // Allow if one is pagrindinis and the other is padalinys
            $sourceIsPagrindinis = $sourceTenantType === 'pagrindinis';
            $targetIsPagrindinis = $targetTenantType === 'pagrindinis';
            $sourceIsPadalinys = $sourceTenantType === 'padalinys';
            $targetIsPadalinys = $targetTenantType === 'padalinys';

            return ($sourceIsPagrindinis && $targetIsPadalinys)
                || ($sourceIsPadalinys && $targetIsPagrindinis);
        }

        // Unknown scope - default to within-tenant behavior
        return $sourceInstitution->tenant_id === $targetInstitution->tenant_id;
    }

    protected static function getTenantType(Institution $institution): ?string
    {
        $tenant = $institution->tenant;

        if (! $tenant || $tenant->type === null || $tenant->type === '') {
            $tenant = Tenant::find($institution->tenant_id);
        }

        return $tenant?->type;
    }

    protected static function isTypeRelationshipAuthorized(
        Institution $sourceInstitution,
        string $scope,
        bool $isBidirectional,
        string $direction
    ): bool {
        if ($scope === Relationshipable::SCOPE_CROSS_TENANT) {
            if ($isBidirectional) {
                return true;
            }

            return self::getTenantType($sourceInstitution) === 'pagrindinis';
        }

        if ($direction === 'outgoing') {
            return true;
        }

        return $isBidirectional;
    }

    /**
     * Get related institutions for multiple institutions efficiently.
     * Used for dashboard view where we need related institutions for all user's institutions.
     *
     * Only authorized relationships (outgoing/sibling) get full data with meetings;
     * unauthorized relationships (incoming) only get basic institution info for display.
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
                if (in_array($relatedInst->getKey(), $institutionIds)) {
                    continue;
                }

                // Skip if we already have this institution with authorized access
                // (keep the one with highest access level)
                $existing = $allRelated->firstWhere('id', $relatedInst->getKey());
                if ($existing) {
                    // If existing is already authorized, skip this one
                    if ($existing->getAttribute('authorized')) {
                        continue;
                    }
                    // If new one is authorized, replace the existing
                    if ($item['authorized']) {
                        $allRelated = $allRelated->reject(fn ($inst) => $inst->getKey() === $relatedInst->getKey());
                    } else {
                        continue;
                    }
                }

                // Add metadata to the institution for frontend use
                $relatedInst->setAttribute('is_related', true);
                $relatedInst->setAttribute('relationship_direction', $item['direction']);
                $relatedInst->setAttribute('relationship_type', $item['type']);
                $relatedInst->setAttribute('source_institution_id', $item['source_institution_id']);
                $relatedInst->setAttribute('authorized', $item['authorized']);

                $allRelated->push($relatedInst);
            }
        }

        // Convert to Eloquent collection and load data
        if ($allRelated->isEmpty()) {
            return new Collection;
        }

        // Separate authorized and unauthorized institution IDs
        $authorizedIds = $allRelated->filter(fn ($inst) => $inst->getAttribute('authorized'))->pluck('id')->toArray();
        $unauthorizedIds = $allRelated->reject(fn ($inst) => $inst->getAttribute('authorized'))->pluck('id')->toArray();

        $loadedInstitutions = new Collection;

        // Load authorized institutions with full data for Gantt display
        if (! empty($authorizedIds)) {
            $authorizedInstitutions = Institution::whereIn('id', $authorizedIds)
                ->with([
                    'types',
                    'meetings:id,title,start_time,type',
                    'meetings.agendaItems:id,meeting_id,title,student_vote,decision,student_benefit',
                    'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                    'tenant:id,shortname',
                    'duties.users:id,name,email,profile_photo_path',
                    'duties.types:id,title,slug',
                    'checkIns',
                ])
                ->get();
            $loadedInstitutions = $loadedInstitutions->merge($authorizedInstitutions);
        }

        // Load unauthorized institutions with minimal data (meetings without agenda items, but still show duty users)
        if (! empty($unauthorizedIds)) {
            $unauthorizedInstitutions = Institution::whereIn('id', $unauthorizedIds)
                ->with([
                    'types',
                    'meetings:id,title,start_time,type', // Meetings for Gantt display, but no agenda items
                    'meetings.fileableFiles:id,fileable_id,fileable_type,file_type,deleted_externally_at',
                    'tenant:id,shortname',
                    'duties.users:id,name,email,profile_photo_path',
                    'duties.types:id,title,slug',
                ])
                ->get();
            $loadedInstitutions = $loadedInstitutions->merge($unauthorizedInstitutions);
        }

        // Merge back the metadata
        $metaMap = $allRelated->keyBy('id');
        $loadedInstitutions->each(function ($inst) use ($metaMap) {
            $meta = $metaMap->get($inst->getKey());
            if ($meta) {
                $inst->is_related = $meta->is_related;
                $inst->relationship_direction = $meta->relationship_direction;
                $inst->relationship_type = $meta->relationship_type;
                $inst->source_institution_id = $meta->source_institution_id;
                $inst->authorized = $meta->authorized;
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

    /**
     * Get related institutions for a single institution.
     *
     * This method now uses getRelatedInstitutionsFlat internally for consistency,
     * which includes sibling relationships (same type + same tenant).
     *
     * @param  bool  $authorizedOnly  If true, only returns institutions where the source has access (outgoing/sibling)
     * @return Collection<int, Institution>
     */
    public static function getRelatedInstitutions(Institution $institution, bool $authorizedOnly = false): Collection
    {
        $flat = self::getRelatedInstitutionsFlat($institution);

        if ($authorizedOnly) {
            $flat = $flat->filter(fn ($item) => $item['authorized'] === true);
        }

        /** @var array<int, Institution> $institutions */
        $institutions = $flat->pluck('institution')->unique('id')->values()->all();

        return new Collection($institutions);
    }

    public static function getAllRelatedInstitutions()
    {
        $institutionRelationshipables = collect(Relationshipable::where('relationshipable_type', Institution::class)->get(['relationshipable_id', 'related_model_id'])->toArray()); // OK

        // we need to get all institutions which are related to the institution
        $typeRelationshipables = Relationshipable::where('relationshipable_type', Type::class)->get()->map(function ($relationshipable) {
            return self::getGivenModelsFromModelType(Institution::class, $relationshipable);
        })->flatten(1);

        // Within-type sibling relationships (institutions with same type + same tenant)
        $withinTypeRelationships = self::getWithinTypeSiblingRelationships();

        $institutionRelationships = $institutionRelationshipables->merge($typeRelationshipables)->merge($withinTypeRelationships);

        return $institutionRelationships;
    }

    /**
     * Get all sibling relationships for types that have enable_sibling_relationships enabled.
     * Returns pairs of institutions that share the same type and tenant.
     */
    protected static function getWithinTypeSiblingRelationships(): \Illuminate\Support\Collection
    {
        $relationships = collect();

        // Find types that have sibling relationships enabled
        $typesWithSiblings = Type::forInstitutions()
            ->whereJsonContains('extra_attributes->enable_sibling_relationships', true)
            ->with(['institutions' => fn ($q) => $q->select('institutions.id', 'institutions.tenant_id')])
            ->get();

        foreach ($typesWithSiblings as $type) {
            // Group institutions by tenant
            $institutionsByTenant = $type->institutions->groupBy('tenant_id');

            foreach ($institutionsByTenant as $tenantId => $institutions) {
                // Create relationships between all pairs within the same tenant
                $institutionIds = $institutions->pluck('id')->values();
                $count = $institutionIds->count();

                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        // Add both directions for the graph
                        $relationships->push([
                            'relationshipable_id' => $institutionIds[$i],
                            'related_model_id' => $institutionIds[$j],
                        ]);
                    }
                }
            }
        }

        return $relationships->unique(fn ($r) => $r['relationshipable_id'].'_'.$r['related_model_id']);
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
                    'relationshipable_id' => $giver->getKey(),
                    'related_model_id' => $receiver->getKey(),
                ];
            });
        });

        // This may take a long time to run as it's not eager loaded, need to refactor but not urgent
        return $relationships;
    }
}
