<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use App\Services\SharepointGraphService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Document::class);

        $indexer = new ModelIndexer(new Document);

        $documents = $indexer
            ->setEloquentQuery([fn (Builder $query) => $query->with(['institution'])])
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(20);

        return Inertia::render('Admin/Files/IndexDocument', [
            'documents' => $documents,
        ]);
    }

    /**
     * Store multiple documents in storage.
     */
    public function store(StoreDocumentRequest $request)
    {
        $documentCollection = new Collection;

        foreach ($request->documents as $document) {
            $model = new Document;

            $model->name = $document['name'];
            $model->title = $document['name'];
            $model->sharepoint_id = $document['list_item_unique_id'];
            $model->sharepoint_site_id = $document['site_id'];
            $model->sharepoint_list_id = $document['list_id'];

            $documentCollection->push($model);

            /*$model->save();*/
        }

        $graph = new SharepointGraphService(siteId: $model->sharepoint_site_id);

        $documentCollection = $graph->batchProcessDocuments($documentCollection);

        return redirect()->route('documents.index')->with('success', 'Documents have been successfully stored.');
    }

    public function refresh(Document $document)
    {
        $this->authorize('update', $document);

        $result = $document->refreshFromSharepoint();

        if ($result === null) {
            return redirect()->route('documents.index')->with('info', 'Document is already up to date.');
        }

        return redirect()->route('documents.index')->with('success', 'Document has been successfully refreshed.');
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
