<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetUserTenantShortnames;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\SharepointGraphService;
use App\Settings\DocumentSettings;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;

class DocumentController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexDocumentRequest $request, DocumentSettings $documentSettings)
    {
        $this->handleAuthorization('viewAny', Document::class);

        $user = $request->user();

        return $this->inertiaResponse('Admin/Files/IndexDocument', [
            'importantContentTypes' => $documentSettings->getImportantContentTypes()->toArray(),
            'abilities' => [
                'create' => $user->can('create', Document::class),
                'update' => $this->authorizer->allows($user, 'documents.update.padalinys'),
                'delete' => $this->authorizer->allows($user, 'documents.delete.padalinys'),
            ],
            // Central office documents matter to everyone, so VU SA joins the user's own padaliniai.
            'defaultTenantShortnames' => GetUserTenantShortnames::execute($user, withMainTenant: true),
        ]);
    }

    /**
     * Store multiple documents in storage.
     */
    public function store(StoreDocumentRequest $request)
    {
        $documentCollection = new Collection;
        $model = null; // Initialize model variable

        foreach ($request->documents as $document) {
            $model = new Document;

            $model->name = $document['name'];
            $model->title = $document['name'];
            $model->sharepoint_id = $document['list_item_unique_id'];
            $model->sharepoint_site_id = $document['site_id'];
            $model->sharepoint_list_id = $document['list_id'];

            $documentCollection->push($model);
        }

        // Check if documents array is not empty
        if ($model === null) {
            return redirect()->route('documents.index')->with('info', __('messages.document.none_to_process'));
        }

        $graph = new SharepointGraphService(siteId: $model->sharepoint_site_id, driveId: config('filesystems.sharepoint.archive_drive_id'));

        $documentCollection = $graph->batchProcessDocuments($documentCollection);

        return redirect()->route('documents.index')->with('success', __('messages.document.stored'));
    }

    public function refresh(Document $document)
    {
        $this->handleAuthorization('update', $document);

        // Dispatch sync job to background instead of synchronous processing
        SyncDocumentFromSharePointJob::dispatch($document);

        return back()->with('success', __('messages.document.refresh_queued'));
    }

    /**
     * Bulk sync all documents from SharePoint
     */
    public function bulkSync()
    {
        // Not `viewAny`: every admin may browse documents, only managers queue syncs.
        $this->authorize('create', Document::class);

        // Get all documents that need syncing (failed, pending, or outdated)
        $documents = Document::where(function ($query): void {
            $query->where('sync_status', '!=', 'success')
                ->orWhere('checked_at', '<', now()->subHours(24))
                ->orWhereNull('checked_at');
        })->get();

        // Dispatch sync jobs for each document
        foreach ($documents as $document) {
            SyncDocumentFromSharePointJob::dispatch($document);
        }

        return back()->with('success', __('messages.document.bulk_sync_queued', ['count' => $documents->count()]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        $this->handleAuthorization('view', $document);

        if ($document->anonymous_url) {
            return Inertia::location($document->anonymous_url);
        }

        return redirect()->route('documents.index')->with('info', __('Dokumentas neturi viešosios nuorodos.'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDocumentRequest $request, Document $document)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $this->handleAuthorization('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', $this->entityMessage('deleted', 'document'));
    }
}
