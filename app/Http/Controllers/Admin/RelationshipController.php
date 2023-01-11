<?php

namespace App\Http\Controllers\Admin;

use App\Models\Relationship;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller as Controller;
use App\Models\Relationshipable;
use App\Services\RelationshipService;
use Illuminate\Support\Facades\DB;

// Controller is used for the relationship object, which describes
// content related relationships.

class RelationshipController extends Controller
{
    
    public function __construct()
    {
        $this->authorizeResource(Relationship::class, 'relationship');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', [Institution::class, $this->authorizer]);
        return Inertia::render('Admin/ModelMeta/IndexRelationships', [
            'relationships' => Relationship::all()->paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return Inertia::render('Admin/ModelMeta/CreateRelationship');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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
     *
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Http\Response
     */
    public function show(Relationship $relationship)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Http\Response
     */
    public function edit(Relationship $relationship, Request $request)
    {
        $validated = $request->validate([
            'modelType' => 'nullable|string',
        ]);
        
        // get model type from request
        $model_type = $validated['modelType'] ?? null;
        $related_models = [];

        // if model type is not null, get related models
        // TODO: use a way to check allowed models
        if (!is_null($model_type)) {
            $related_models = RelationshipService::getModelsByClass($model_type);
        }

        $relationship->load('relationshipables', 'relationshipables.relationshipable', 'relationshipables.relatedRelationshipable');
        
        return Inertia::render('Admin/ModelMeta/EditRelationship', [
            'relationship' => $relationship,
            'relatedModels' => Inertia::lazy(fn () => $related_models),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Relationship $relationship)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:relationships,slug,' . $relationship->id,
        ]);

        $relationship->update($request->only('name', 'slug', 'description'));

        return redirect()->route('relationships.index')
            ->with('success', 'Ryšio tipas atnaujintas sėkmingai.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Relationship  $relationship
     * @return \Illuminate\Http\Response
     */
    public function destroy(Relationship $relationship)
    {
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

    public function deleteModelRelationship(Relationshipable $relationshipable) {
        $relationshipable->delete();

        return back()->with('success', 'Ryšys tarp modelių ištrintas.');
    }
}
