<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

// Controller is used for the relationship object, which describes
// content related relationships.

class RelationshipController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Relationship::class);

        return $this->inertiaResponse('Admin/ModelMeta/IndexRelationships', [
            'relationships' => Relationship::all()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Relationship::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateRelationship');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->handleAuthorization('create', Relationship::class);

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:relationships,slug',
        ]);

        Relationship::create($request->only('name', 'slug', 'description'));

        return redirect()->route('relationships.index')
            ->with('success', 'Ryšio tipas sukurtas sėkmingai.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Relationship $relationship)
    {
        $this->handleAuthorization('view', $relationship);

        return $this->inertiaResponse('Admin/ModelMeta/ShowRelationship', [
            'relationship' => $relationship,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Relationship $relationship, Request $request)
    {
        $this->handleAuthorization('update', $relationship);

        $validated = $request->validate([
            'modelType' => 'nullable|string',
        ]);

        // get model type from request
        $model_type = $validated['modelType'] ?? null;
        $related_models = [];

        // if model type is not null, get related models
        // TODO: use a way to check allowed models
        if (! is_null($model_type)) {
            $related_models = RelationshipService::getModelsByClass($model_type);
        }

        $relationship->load('relationshipables', 'relationshipables.relationshipable', 'relationshipables.related_model');

        return $this->inertiaResponse('Admin/ModelMeta/EditRelationship', [
            'relationship' => $relationship,
            'relatedModels' => Inertia::lazy(fn () => $related_models),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Relationship $relationship)
    {
        $this->handleAuthorization('update', $relationship);

        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:relationships,slug,'.$relationship->id,
        ]);

        $relationship->update($request->only('name', 'slug', 'description'));

        return redirect()->route('relationships.index')
            ->with('success', 'Ryšio tipas atnaujintas sėkmingai.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Relationship $relationship)
    {
        $this->handleAuthorization('delete', $relationship);

        DB::transaction(function () use ($relationship) {
            // remove all relationshipables
            DB::table('relationshipables')->where('relationship_id', $relationship->id);
            $relationship->delete();
        });

        return back()->with('success', 'Ryšio tipas tarp modelių ištrintas');
    }

    // Store relationship between models
    public function storeModelRelationship(Request $request, Relationship $relationship)
    {
        $this->handleAuthorization('create', $relationship);

        $request->validate([
            'model_id' => 'required',
            'model_type' => 'required',
            'related_model_id' => 'required',
        ]);

        $relationship->models($request->model_type)->attach($request->model_id, [
            'related_model_id' => $request->related_model_id,
        ]);

        return redirect()->route('relationships.edit', $relationship)
            ->with('success', 'Ryšys sukurtas sėkmingai.');
    }

    public function deleteModelRelationship(Relationshipable $relationshipable)
    {
        $this->handleAuthorization('delete', $relationshipable);

        $relationshipable->delete();

        return back()->with('success', 'Ryšys tarp modelių ištrintas.');
    }
}
