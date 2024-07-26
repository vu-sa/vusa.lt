<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Models\Document;
use App\Services\ModelIndexer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DocumentController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', [Document::class, $this->authorizer]);

        $indexer = new ModelIndexer(new Document, $request, $this->authorizer);

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
        foreach ($request->documents as $document) {
            $model = new Document;

            $model->name = $document['name'];
            $model->title = $document['name'];
            $model->sharepoint_id = $document['list_item_unique_id'];
            $model->sharepoint_site_id = $document['site_id'];
            $model->sharepoint_list_id = $document['list_id'];

            $model->save();
        }

        return redirect()->route('documents.index')->with('success', 'Documents have been successfully stored.');
    }

    public function refresh(Document $document)
    {
        $this->authorize('update', [Document::class, $document, $this->authorizer]);

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
        $this->authorize('delete', [Document::class, $document, $this->authorizer]);

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document has been successfully deleted.');
    }
}
