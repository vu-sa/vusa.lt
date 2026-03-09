<?php

namespace App\Observers;

use App\Models\Institution;
use App\Services\RelationshipService;

class InstitutionObserver
{
    /**
     * Handle the Institution "updated" event.
     * Clear the related institutions cache when an institution is updated
     * (e.g., tenant_id or types change could affect relationships).
     */
    public function updated(Institution $institution): void
    {
        RelationshipService::clearRelatedInstitutionsCache($institution->id);

        // If tenant_id changed, clear caches for all institutions that might be related
        // through type-based relationships in the old or new tenant
        if ($institution->isDirty('tenant_id')) {
            // Clear all related institutions' caches since type-based relationships are tenant-scoped
            $related = RelationshipService::getRelatedInstitutionsFlat($institution);
            foreach ($related as $item) {
                RelationshipService::clearRelatedInstitutionsCache($item['institution']->id);
            }
        }
    }

    /**
     * Handle the Institution "deleted" event.
     * Clear all related caches when an institution is deleted.
     */
    public function deleted(Institution $institution): void
    {
        RelationshipService::clearRelatedInstitutionsCache($institution->id);
    }
}
