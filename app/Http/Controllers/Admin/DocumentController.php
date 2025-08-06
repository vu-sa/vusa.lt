<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Traits\HasTanstackTables;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\SharepointGraphService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Context;
use Inertia\Inertia;

class DocumentController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexDocumentRequest $request)
    {
        $this->handleAuthorization('viewAny', Document::class);

        // Set admin context for indexing all documents
        Context::add('search_context', 'admin');

        // Build Typesense options array
        $options = [];

        // Handle search text - use wildcard for "show all"
        $searchText = $request->input('search', '');
        if (empty($searchText)) {
            // Use q: "*" (wildcard) with filter_by as per Typesense docs
            $searchText = '*';
        }
        // Always set query_by - it's mandatory for Typesense
        $options['query_by'] = 'title,name,summary,content_type,institution_name_lt,institution_name_en';

        // Apply all filters in filter_by (this is the key for wildcard searches)
        $filters = $request->getFilters();
        $filterConditions = [];

        if (! empty($filters['content_type'])) {
            $contentTypeFilters = array_map(fn ($type) => "content_type:=\"{$type}\"", (array) $filters['content_type']);
            $filterConditions[] = '('.implode(' || ', $contentTypeFilters).')';
        }

        if (! empty($filters['language'])) {
            $filterConditions[] = "language:=\"{$filters['language']}\"";
        }

        if (! empty($filters['institution.id'])) {
            $institutionIds = array_map(fn ($id) => "institution_id:={$id}", (array) $filters['institution.id']);
            $filterConditions[] = '('.implode(' || ', $institutionIds).')';
        }

        // Add permission filtering to Typesense options
        if (! $this->authorizer->isAllScope && ! auth()->user()->isSuperAdmin()) {
            $allowedTenants = $this->authorizer->getTenants('documents.read.padalinys');
            if ($allowedTenants->isNotEmpty()) {
                $allowedShortnames = $allowedTenants->pluck('shortname')->toArray();
                $tenantFilter = implode(' || ', array_map(fn ($shortname) => "tenant_shortname:=\"{$shortname}\"", $allowedShortnames));
                $filterConditions[] = "({$tenantFilter})";
            }
        }

        // Always set filter_by, even if empty (important for wildcard searches)
        if (! empty($filterConditions)) {
            $options['filter_by'] = implode(' && ', $filterConditions);
        }

        // Sorting
        $sorting = $request->getSorting();
        if (! empty($sorting)) {
            $sortFields = [];
            foreach ($sorting as $sort) {
                $field = $sort['id'];
                $direction = $sort['desc'] ? 'desc' : 'asc';

                // Map frontend field names to Typesense fields
                $fieldMap = [
                    'document_date' => 'document_date',
                    'created_at' => 'created_at',
                    'checked_at' => 'checked_at',
                    'title' => 'title',
                    'content_type' => 'content_type',
                    'sync_status' => 'sync_status',
                ];

                $typesenseField = $fieldMap[$field] ?? $field;
                $sortFields[] = "{$typesenseField}:{$direction}";
            }
            $options['sort_by'] = implode(',', $sortFields);
        } else {
            $options['sort_by'] = 'created_at:desc';
        }

        $perPage = $request->input('per_page', 20);
        $results = Document::search($searchText)->options($options)->paginate($perPage);

        return $this->inertiaResponse('Admin/Files/IndexDocument', [
            'data' => (new Collection($results->items()))->load('institution.tenant'),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ],
            'filters' => $filters,
            'sorting' => $sorting,
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

            /* $model->save(); */
        }

        // Check if documents array is not empty
        if ($model === null) {
            return redirect()->route('documents.index')->with('info', 'No documents to process.');
        }

        $graph = new SharepointGraphService(siteId: $model->sharepoint_site_id, driveId: config('filesystems.sharepoint.archive_drive_id'));

        $documentCollection = $graph->batchProcessDocuments($documentCollection);

        return redirect()->route('documents.index')->with('success', 'Documents have been successfully stored.');
    }

    public function refresh(Document $document)
    {
        $this->handleAuthorization('update', $document);

        // Dispatch sync job to background instead of synchronous processing
        SyncDocumentFromSharePointJob::dispatch($document);

        return back()->with('success', 'Document refresh has been queued. It will be updated shortly.');
    }

    /**
     * Bulk sync all documents from SharePoint
     */
    public function bulkSync()
    {
        $this->authorize('viewAny', Document::class);

        // Get all documents that need syncing (failed, pending, or outdated)
        $documents = Document::where(function ($query) {
            $query->where('sync_status', '!=', 'success')
                ->orWhere('checked_at', '<', now()->subHours(24))
                ->orWhereNull('checked_at');
        })->get();

        // Dispatch sync jobs for each document
        foreach ($documents as $document) {
            SyncDocumentFromSharePointJob::dispatch($document);
        }

        return back()->with('success', "Bulk sync queued for {$documents->count()} documents. They will be updated shortly.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Document $document)
    {
        //
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

        return redirect()->route('documents.index')->with('success', 'Document has been successfully deleted.');
    }
}
