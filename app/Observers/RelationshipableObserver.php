<?php

namespace App\Observers;

use App\Models\Pivots\Relationshipable;
use App\Services\InstitutionAccessService;
use App\Services\RelationshipService;
use App\Services\Typesense\TypesenseScopedKeyService;

class RelationshipableObserver
{
    /**
     * Handle the Relationshipable "created" event.
     */
    public function created(Relationshipable $relationshipable): void
    {
        $this->invalidateCaches($relationshipable);
    }

    /**
     * Handle the Relationshipable "updated" event.
     */
    public function updated(Relationshipable $relationshipable): void
    {
        $this->invalidateCaches($relationshipable);
    }

    /**
     * Handle the Relationshipable "deleted" event.
     */
    public function deleted(Relationshipable $relationshipable): void
    {
        $this->invalidateCaches($relationshipable);
    }

    /**
     * Invalidate all related caches when a relationship changes.
     *
     * This includes:
     * - RelationshipService cache (related institutions for an institution)
     * - InstitutionAccessService cache (user's accessible institutions)
     * - TypesenseScopedKeyService cache (user's search scoped keys)
     */
    protected function invalidateCaches(Relationshipable $relationshipable): void
    {
        // Clear RelationshipService cache (institution relationships)
        RelationshipService::clearCacheForRelationshipable($relationshipable);

        // Clear InstitutionAccessService and TypesenseScopedKeyService caches for affected users
        // This needs to clear caches for users who have duties in the affected institutions
        InstitutionAccessService::invalidateForRelationshipChange(
            $relationshipable->relationshipable_id,
            $relationshipable->related_model_id
        );

        // Typesense scoped keys should be invalidated for the same users
        // InstitutionAccessService::invalidateForRelationshipChange already handles users
        // We need to also clear their Typesense keys
        $this->invalidateTypesenseKeysForRelationshipChange($relationshipable);
    }

    /**
     * Invalidate Typesense scoped keys for users affected by a relationship change.
     */
    protected function invalidateTypesenseKeysForRelationshipChange(Relationshipable $relationshipable): void
    {
        // Get users from the source institution
        $this->invalidateTypesenseKeysForInstitution($relationshipable->relationshipable_id);

        // Get users from the target institution
        $this->invalidateTypesenseKeysForInstitution($relationshipable->related_model_id);
    }

    /**
     * Invalidate Typesense scoped keys for all users with duties in an institution.
     */
    protected function invalidateTypesenseKeysForInstitution(string $institutionId): void
    {
        $institution = \App\Models\Institution::with('duties.users')->find($institutionId);

        if (! $institution) {
            return;
        }

        foreach ($institution->duties as $duty) {
            foreach ($duty->users as $user) {
                TypesenseScopedKeyService::invalidateForUser($user->id);
            }
        }
    }
}
