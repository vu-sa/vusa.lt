<?php

namespace App\Observers;

use App\Models\Type;
use App\Services\RelationshipService;

class TypeObserver
{
    /**
     * Handle the Type "updated" event.
     * Clears relationship cache for all institutions with this type
     * when enable_sibling_relationships setting changes.
     */
    public function updated(Type $type): void
    {
        // Check if extra_attributes changed (specifically enable_sibling_relationships)
        if ($type->wasChanged('extra_attributes')) {
            $oldValue = $type->getOriginal('extra_attributes')['enable_sibling_relationships'] ?? false;
            $newValue = $type->extra_attributes['enable_sibling_relationships'] ?? false;

            // Only clear cache if the sibling relationships setting changed
            if ($oldValue !== $newValue) {
                $this->clearCacheForTypeInstitutions($type);
            }
        }
    }

    /**
     * Clear the relationship cache for all institutions that have this type.
     */
    protected function clearCacheForTypeInstitutions(Type $type): void
    {
        $type->institutions()->pluck('id')->each(function ($institutionId) {
            RelationshipService::clearRelatedInstitutionsCache($institutionId);
        });
    }
}
