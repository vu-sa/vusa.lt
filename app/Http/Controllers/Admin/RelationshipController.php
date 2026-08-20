<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexRelationshipRequest;
use App\Http\Requests\Relationships\EditRelationshipRequest;
use App\Http\Requests\Relationships\StoreModelRelationshipRequest;
use App\Http\Requests\Relationships\StoreRelationshipRequest;
use App\Http\Requests\Relationships\UpdateModelRelationshipRequest;
use App\Http\Requests\Relationships\UpdateRelationshipRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Type;
use App\Services\RelationshipService;
use App\Services\TanstackTableService;
use App\Support\MorphMap;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

// Controller is used for the relationship object, which describes
// content related relationships.

class RelationshipController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRelationshipRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Relationship::class);

        $query = Relationship::query();

        $searchableColumns = ['name', 'slug', 'description'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $relationships = $query->paginate($request->getPerPage())
            ->withQueryString();

        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/ModelMeta/IndexRelationships', [
            'relationships' => [
                'data' => $relationships->items(),
                'meta' => [
                    'total' => $relationships->total(),
                    'per_page' => $relationships->perPage(),
                    'current_page' => $relationships->currentPage(),
                    'last_page' => $relationships->lastPage(),
                    'from' => $relationships->firstItem(),
                    'to' => $relationships->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
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

        return $this->inertiaResponse('Admin/ModelMeta/ShowRelationship', [
            'relationship' => $relationship,
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
            // remove all relationshipables
            DB::table('relationshipables')->where('relationship_id', $relationship->id);
            $relationship->delete();
        });

        return back()->with('success', __('messages.relationship.type_model_relation_deleted'));
    }

    // Store relationship between models
    public function storeModelRelationship(StoreModelRelationshipRequest $request, Relationship $relationship)
    {
        $this->handleAuthorization('create', $relationship);

        $pivotData = [
            'related_model_id' => $request->related_model_id,
            'bidirectional' => $request->boolean('bidirectional', false),
        ];

        // Only add scope for Type-based relationships
        if ($request->model_type === MorphMap::alias(Type::class)) {
            $pivotData['scope'] = $request->scope ?? 'within-tenant';
        }

        $relationship->models($request->model_type)->attach($request->model_id, $pivotData);

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
