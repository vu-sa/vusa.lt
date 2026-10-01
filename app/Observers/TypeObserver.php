<?php

namespace App\Observers;

use App\Models\InstitutionType;
use App\Services\RelationshipService;

class TypeObserver
{
    /**
     * Handle the InstitutionType "updated" event.
     * Clears relationship cache for all institutions with this type
     * when sibling relationship settings change.
     */
    public function updated(InstitutionType $type): void
    {
        // Check if extra_attributes changed (sibling relationship settings)
        if ($type->wasChanged('extra_attributes')) {
            $oldAttributes = $type->getOriginal('extra_attributes') ?? [];
            $newAttributes = $type->extra_attributes ?? [];

            $oldSiblings = $oldAttributes['enable_sibling_relationships'] ?? false;
            $newSiblings = $newAttributes['enable_sibling_relationships'] ?? false;

            $oldCrossTenantSiblings = $oldAttributes['enable_cross_tenant_sibling_relationships'] ?? false;
            $newCrossTenantSiblings = $newAttributes['enable_cross_tenant_sibling_relationships'] ?? false;

            // Clear cache if any sibling relationship setting changed
            if ($oldSiblings !== $newSiblings || $oldCrossTenantSiblings !== $newCrossTenantSiblings) {
                $this->clearCacheForTypeInstitutions($type);
            }
        }
    }

    /**
     * Clear the relationship cache for all institutions that have this type.
     */
    protected function clearCacheForTypeInstitutions(InstitutionType $type): void
    {
        $type->institutions()->pluck('id')->each(function ($institutionId): void {
            RelationshipService::clearRelatedInstitutionsCache($institutionId);
        });
    }
}
