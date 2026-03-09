<?php

namespace App\Observers;

use App\Jobs\RevokeSharepointPermissionJob;
use App\Models\Document;

class DocumentObserver
{
    /**
     * Handle the Document "deleting" event.
     *
     * Revoke the SharePoint anonymous permission before the document is deleted.
     */
    public function deleting(Document $document): void
    {
        $this->dispatchRevocationIfNeeded($document);
    }

    /**
     * Handle the Document "updating" event.
     *
     * When is_active changes from true to false, revoke access and clear the URL.
     */
    public function updating(Document $document): void
    {
        if ($document->isDirty('is_active') && $document->getOriginal('is_active') === true && $document->is_active === false) {
            $this->dispatchRevocationIfNeeded($document);

            $document->anonymous_url = null;
            $document->sharepoint_permission_id = null;
        }
    }

    /**
     * Dispatch the revocation job if the document has a stored permission ID.
     */
    private function dispatchRevocationIfNeeded(Document $document): void
    {
        if (! $document->sharepoint_permission_id || ! $document->anonymous_url) {
            return;
        }

        RevokeSharepointPermissionJob::dispatch(
            sharepointSiteId: $document->sharepoint_site_id,
            sharepointListId: $document->sharepoint_list_id,
            sharepointId: $document->sharepoint_id,
            sharepointPermissionId: $document->sharepoint_permission_id,
            documentId: $document->id,
        );
    }
}
