<?php

namespace App\Observers;

use App\Actions\GetAttachableTypesForDuty;
use App\Models\Institution;
use App\Models\Typeable;
use App\Services\RelationshipService;

class TypeableObserver
{
    /**
     * Handle the Typeable "saved" event.
     */
    public function saved(Typeable $typeable): void
    {
        // Clear relationship cache when an institution's types change
        // This is needed for within-type sibling relationships
        if ($typeable->getAttribute('typeable_type') === Institution::class) {
            RelationshipService::clearRelatedInstitutionsCache($typeable->getAttribute('typeable_id'));
        }

        if ($typeable->getAttribute('typeable_type') === 'App\Models\Duty') {
            $attachable_types = GetAttachableTypesForDuty::execute();

            if ($attachable_types->contains($typeable->getAttribute('type_id'))) {
                $roles = $typeable->type->roles;

                // add each role to the duty (ensure it's a Duty model)
                if ($typeable->typeable instanceof \App\Models\Duty) {
                    $typeable->typeable->roles()->syncWithoutDetaching($roles);
                }
            }
        }
    }

    /**
     * Handle the Typeable "deleted" event.
     */
    public function deleted(Typeable $typeable): void
    {
        // Clear relationship cache when an institution's types change
        if ($typeable->getAttribute('typeable_type') === Institution::class) {
            RelationshipService::clearRelatedInstitutionsCache($typeable->getAttribute('typeable_id'));
        }

        if (get_class($typeable->pivotParent) === 'App\Models\Duty') {
            $typeRoles = $typeable->type->roles;

            // remove each role from the duty
            $typeable->pivotParent->roles()->detach($typeRoles);
        }
    }
}
