<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IndexDocumentRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Document;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\SharepointGraphService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;

class DocumentController extends Controller
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexDocumentRequest $request)
    {
        $this->authorize('viewAny', Document::class);

        // Build base query with eager loading
        $query = Document::query()->with(['institution']);

        // Define searchable columns
        $searchableColumns = ['name', 'title', 'institution.name', 'sharepoint_id'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'tenantRelation' => 'institution.tenant',
                'permission' => 'documents.read.padalinys',
                'applySortBeforePagination' => true,
            ]
        );

        // Paginate results
        $documents = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        // Get the sorting state using the custom method to ensure consistent parsing
        $sorting = $request->getSorting();

        return Inertia::render('Admin/Files/IndexDocument', [
            'data' => $documents->items(),
            'meta' => [
                'total' => $documents->total(),
                'per_page' => $documents->perPage(),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'from' => $documents->firstItem(),
                'to' => $documents->lastItem(),
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'initialSorting' => $sorting,
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

        // Check if model is defined (documents array is not empty)
        if ($model === null || $documentCollection->isEmpty()) {
            return redirect()->route('documents.index')->with('info', 'No documents to process.');
        }

        $graph = new SharepointGraphService(siteId: $model->sharepoint_site_id, driveId: config('filesystems.sharepoint.archive_drive_id'));

        $documentCollection = $graph->batchProcessDocuments($documentCollection);

        return redirect()->route('documents.index')->with('success', 'Documents have been successfully stored.');
    }

    public function refresh(Document $document)
    {
        $this->authorize('update', $document);

        $result = $document->refreshFromSharepoint();

        if ($result === null) {
            return back()->with('info', 'Document is already up to date.');
        }

        return back()->with('success', 'Document has been successfully refreshed.');
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
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document has been successfully deleted.');
    }
}
