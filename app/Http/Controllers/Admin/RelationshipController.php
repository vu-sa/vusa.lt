<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexRelationshipRequest;
use App\Http\Requests\Relationships\EditRelationshipRequest;
use App\Http\Requests\Relationships\StoreModelRelationshipRequest;
use App\Http\Requests\Relationships\StoreRelationshipRequest;
use App\Http\Requests\Relationships\UpdateModelRelationshipRequest;
use App\Http\Requests\Relationships\UpdateRelationshipRequest;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Type;
use App\Services\RelationshipService;
use App\Support\MorphMap;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

// Controller is used for the relationship object, which describes
// content related relationships.

class RelationshipController extends AdminController
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRelationshipRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Relationship::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        return $this->inertiaResponse('Admin/ModelMeta/IndexRelationships', [
            'relationships' => Relationship::query()->orderBy('name')->get(['id', 'name', 'slug', 'description']),
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
    public function store(StoreRelationshipRequest $request)
    {
        $this->handleAuthorization('create', Relationship::class);

        Relationship::create($request->safe()->only('name', 'slug', 'description'));

        return redirect()->route('relationships.index')
            ->with('success', $this->entityMessage('created', 'relationshipType'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Relationship $relationship)
    {
        $this->handleAuthorization('view', $relationship);

        $relationship->load('relationshipables.relationshipable', 'relationshipables.related_model');

        return $this->inertiaResponse('Admin/ModelMeta/ShowRelationship', [
            'relationship' => [
                ...$relationship->toArray(),
                'relationshipables' => $relationship->relationshipables->map(fn (Relationshipable $relationshipable): array => [
                    'id' => $relationshipable->id,
                    'source' => $relationshipable->relationshipable?->only(['id', 'name', 'title']),
                    'target' => $relationshipable->related_model?->only(['id', 'name', 'title']),
                    'type' => $relationshipable->relationshipable_type,
                    'scope' => $relationshipable->scope,
                    'bidirectional' => $relationshipable->bidirectional,
                ])->values(),
            ],
            'can' => [
                'update' => auth()->user()?->can('update', $relationship) ?? false,
                'delete' => auth()->user()?->can('delete', $relationship) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Relationship $relationship, EditRelationshipRequest $request)
    {
        $this->handleAuthorization('update', $relationship);

        // get model type from request
        $model_type = $request->validated('modelType');
        $related_models = [];

        // getModelsByClass() already refuses anything outside
        // AllowedRelationshipablesEnum and returns an empty list instead.
        if (! is_null($model_type)) {
            $related_models = RelationshipService::getModelsByClass($model_type);
        }

        $relationship->load('relationshipables', 'relationshipables.relationshipable', 'relationshipables.related_model');

        return $this->inertiaResponse('Admin/ModelMeta/EditRelationship', [
            'relationship' => $relationship,
            'relatedModels' => Inertia::optional(fn () => $related_models),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRelationshipRequest $request, Relationship $relationship)
    {
        $this->handleAuthorization('update', $relationship);

        $relationship->update($request->safe()->only('name', 'slug', 'description'));

        return redirect()->route('relationships.index')
            ->with('success', $this->entityMessage('updated', 'relationshipType'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Relationship $relationship)
    {
        $this->handleAuthorization('delete', $relationship);

        DB::transaction(function () use ($relationship): void {
            $relationship->relationshipables()->delete();
            $relationship->delete();
        });

        // The record is gone, so its show/edit page (the usual referrer) would 404.
        return redirect()->route('relationships.index')->with('success', __('messages.relationship.type_model_relation_deleted'));
    }

    // Store relationship between models
    public function storeModelRelationship(StoreModelRelationshipRequest $request, Relationship $relationship)
    {
        $this->handleAuthorization('create', $relationship);

        $validated = $request->validated();

        $pivotData = [
            'related_model_id' => $validated['related_model_id'],
            'bidirectional' => $request->boolean('bidirectional'),
        ];

        // Only add scope for Type-based relationships
        if ($validated['model_type'] === MorphMap::alias(Type::class)) {
            $pivotData['scope'] = $validated['scope'] ?? 'within-tenant';
        }

        $relationship->models($validated['model_type'])->attach($validated['model_id'], $pivotData);

        return redirect()->route('relationships.edit', $relationship)
            ->with('success', $this->entityMessage('created', 'relationship'));
    }

    public function updateModelRelationship(UpdateModelRelationshipRequest $request, Relationshipable $relationshipable)
    {
        $this->handleAuthorization('update', $relationshipable);

        $updateData = [
            'bidirectional' => $request->boolean('bidirectional', false),
        ];

        // Only update scope for Type-based relationships
        if ($relationshipable->relationshipable_type === MorphMap::alias(Type::class) && $request->has('scope')) {
            $updateData['scope'] = $request->scope;
        }

        $relationshipable->update($updateData);

        return back()->with('success', $this->entityMessage('updated', 'relationship'));
    }

    public function deleteModelRelationship(Relationshipable $relationshipable)
    {
        $this->handleAuthorization('delete', $relationshipable);

        $relationshipable->delete();

        return back()->with('success', __('messages.relationship.model_relation_deleted'));
    }
}
