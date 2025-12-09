<?php

namespace App\Observers;

use App\Models\Pivots\Relationshipable;
use App\Services\RelationshipService;

class RelationshipableObserver
{
    /**
     * Handle the Relationshipable "created" event.
     */
    public function created(Relationshipable $relationshipable): void
    {
        RelationshipService::clearCacheForRelationshipable($relationshipable);
    }

    /**
     * Handle the Relationshipable "updated" event.
     */
    public function updated(Relationshipable $relationshipable): void
    {
        RelationshipService::clearCacheForRelationshipable($relationshipable);
    }

    /**
     * Handle the Relationshipable "deleted" event.
     */
    public function deleted(Relationshipable $relationshipable): void
    {
        RelationshipService::clearCacheForRelationshipable($relationshipable);
    }
}
