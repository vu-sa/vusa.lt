<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Documents\ResolvePickedDocuments;
use App\Actions\Documents\UpdateDocumentStatus;
use App\Actions\GetUserTenantShortnames;
use App\Enums\DocumentStatus;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexDocumentRequest;
use App\Http\Requests\PickSharepointDocumentsRequest;
use App\Http\Requests\UpdateDocumentStatusRequest;
use App\Jobs\DiscoverSharepointDocumentsJob;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Services\ModelAuthorizer as Authorizer;
use App\Settings\DocumentDiscoverySettings;
use App\Settings\DocumentSettings;
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
        $canCreate = $user->can('create', Document::class);

        // The old separate views are now filters of the one manager view.
        if ($canCreate && ($request->filled('queue') || $request->filled('browse'))) {
            return redirect()->route('documents.index', array_filter([
                'status' => $request->validated('queue'),
                'layout' => $request->validated('browse') === 'folders' ? 'folders' : ($request->filled('queue') ? 'list' : null),
            ]));
        }

        $updateScope = $this->authorizer->scope($user, 'documents.update.padalinys');
        $manageable = fn () => Document::query()->manageableBy($user);

        return $this->inertiaResponse('Admin/Files/IndexDocument', [
            'importantContentTypes' => $documentSettings->getImportantContentTypes()->toArray(),
            // Managers get the database-backed view of every file; others browse the published archive.
            'discovery' => $canCreate ? [
                'lastRunAt' => app(DocumentDiscoverySettings::class)->last_run_at,
                'counts' => [
                    'pending' => $manageable()->whereNull('removed_from_sharepoint_at')->where('status', DocumentStatus::Pending)->count(),
                    'removed' => $manageable()->whereNotNull('removed_from_sharepoint_at')->count(),
                ],
            ] : null,
            'abilities' => [
                'create' => $canCreate,
                'update' => $updateScope->granted,
                // Archive rows come from search, so their actions are decided by padalinys in the browser; null is every one.
                'updateTenantShortnames' => $updateScope->isAllScope ? null : $updateScope->tenants->pluck('shortname')->values()->all(),
            ],
            // Central office documents matter to everyone, so VU SA joins the user's own padaliniai.
            'defaultTenantShortnames' => GetUserTenantShortnames::execute($user, withMainTenant: true),
        ]);
    }

    /**
     * Publish (queues the public link) or hide (revokes it, see DocumentObserver) SharePoint files.
     */
    public function updateStatus(UpdateDocumentStatusRequest $request)
    {
        return back()->with('success', UpdateDocumentStatus::executeWithMessage($request->documents(), $request->status(), $request->user()));
    }

    /**
     * Publish files chosen in SharePoint's own picker, importing any discovery has not reached yet.
     */
    public function pick(PickSharepointDocumentsRequest $request)
    {
        $documents = ResolvePickedDocuments::execute($request->pickedItems(), $request->user());
        UpdateDocumentStatus::execute($documents, DocumentStatus::Published, $request->user());

        // The picker gives no chance to warn before publishing, so the missing data is named afterwards.
        $incomplete = $documents->filter(fn (Document $document): bool => $document->metadataProblems() !== []);

        return back()->with(array_filter([
            'success' => __('messages.document.picked_published', ['count' => $documents->count()]),
            'toast_description' => $incomplete->isEmpty() ? null : __('messages.document.picked_incomplete', [
                'titles' => $incomplete->map(fn (Document $document): string => $document->title ?: $document->name)->join(', '),
            ]),
        ]));
    }

    /**
     * Read new and changed files from SharePoint now instead of waiting for the 15-minute run.
     */
    public function discover()
    {
        $this->authorize('create', Document::class);

        DiscoverSharepointDocumentsJob::dispatch();

        return back()->with('success', __('messages.document.discovery_queued'));
    }

    public function refresh(Document $document)
    {
        $this->handleAuthorization('update', $document);

        // Dispatch sync job to background instead of synchronous processing
        // Forced: a person asking for a refresh means "check the link too", not "unless the eTag matches".
        SyncDocumentFromSharePointJob::dispatch($document, force: true);

        return back()->with('success', __('messages.document.refresh_queued'));
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
}
