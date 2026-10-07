<?php

namespace App\Jobs;

use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * One SharePoint link job per document at a time, across job classes: a revocation and a sync running
 * together could delete the link the sync has just stored.
 */
class DocumentSharepointLock
{
    public static function for(int|string $documentId): WithoutOverlapping
    {
        return (new WithoutOverlapping('document-sharepoint:'.$documentId))
            ->shared()
            ->releaseAfter(10)
            // Above SyncDocumentFromSharePointJob's timeout, so a killed worker's lock still frees itself.
            ->expireAfter(120);
    }
}
