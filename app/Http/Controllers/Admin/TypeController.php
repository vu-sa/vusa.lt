<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Role;
use App\Models\Type;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TypeController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTypeRequest $request)
    {
        $this->handleAuthorization('viewAny', Type::class);

        // Build base query with eager loading
        $query = Type::query();

        // Define searchable columns
        $searchableColumns = ['title', 'model_type', 'slug'];

        // Apply Tanstack Table filters
        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        // Paginate results
        $types = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        // Get the sorting state using the custom method to ensure consistent parsing
        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/ModelMeta/IndexTypes', [
            'data' => $types->items(),
            'meta' => [
                'total' => $types->total(),
                'per_page' => $types->perPage(),
                'current_page' => $types->currentPage(),
                'last_page' => $types->lastPage(),
                'from' => $types->firstItem(),
                'to' => $types->lastItem(),
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
            'initialSorting' => $sorting,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Type::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateType', [
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
            'roles' => Role::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->handleAuthorization('create', Type::class);

        $request->validate([
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'model_type' => 'string|required',
            'parent_id' => 'nullable|exists:types,id|different:id',
            'roles' => 'nullable|array',
            'slug' => 'nullable|string',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
        ]);

        // TODO: somehow check if model_type is valid and allowed

        $type = Type::query()->create($request->only('title', 'model_type', 'description', 'parent_id', 'slug', 'extra_attributes'));

        if ($request['model_type'] === 'App\Models\Duty') {
            $type->roles()->sync($request->input('roles', []));
        }

        return redirect()->route('types.index')
            ->with('success', 'Turinio tipas sukurtas sėkmingai.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Type $type)
    {
        $this->handleAuthorization('view', $type);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'contentType' => $type->toArray(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Type $type)
    {
        $this->handleAuthorization('update', $type);

        $modelType = Str::of($type->model_type)->afterLast('\\')->lower()->plural()->toString();

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'contentType' => [
                ...$type->load($modelType)->toFullArray(),
                'roles' => $type->roles->pluck('id')->toArray(),
            ],
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
            'sharepointPath' => $type->sharepoint_path(),
            'allModelsFromModelType' => $type->allModelsFromModelType()->toArray(),
            'modelType' => $modelType,
            'roles' => Role::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Type $type)
    {
        $this->handleAuthorization('update', $type);

        $request->validate([
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'model_type' => 'required',
            'parent_id' => 'nullable|exists:types,id|different:id',
            'extra_attributes' => 'nullable|array',
            'extra_attributes.meeting_periodicity_days' => 'nullable|integer|min:1|max:365',
        ]);

        $type->update($request->only('title', 'model_type', 'description', 'parent_id', 'extra_attributes'));

        $modelType = Str::of($request->model_type)->afterLast('\\')->lower()->plural()->toString();

        $type->$modelType()->sync($request->input($modelType, []));

        if ($request['model_type'] === 'App\Models\Duty') {
            $type->roles()->sync($request->input('roles', []));
        }

        return back()->with('success', 'Turinio tipas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Type $type)
    {
        $this->handleAuthorization('delete', $type);

        $type->delete();

        return redirect()->route('types.index')
            ->with('success', 'Turinio tipas ištrintas sėkmingai.');
    }

    public function restore(Type $type)
    {
        $this->handleAuthorization('restore', $type);

        $type->restore();

        return back()->with('success', 'Tipas atkurtas!');
    }
}
