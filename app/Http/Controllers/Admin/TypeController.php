<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\StoreTypeRequest;
use App\Http\Requests\UpdateTypeRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Type;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;

class TypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTypeRequest $request)
    {
        $this->handleAuthorization('viewAny', Type::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        $types = Type::query()
            ->when($request->getShowDeleted(), fn ($query) => $query->onlyTrashed())
            ->orderBy('model_type')
            ->get();

        return $this->inertiaResponse('Admin/ModelMeta/IndexTypes', [
            'types' => $this->appendForceDeleteBlockedReason($types, $request)->values(),
            'deletedCount' => Type::onlyTrashed()->count(),
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
    public function store(StoreTypeRequest $request)
    {
        $validated = $request->validated();

        $type = Type::query()->create(
            $request->safe()->only('title', 'model_type', 'description', 'parent_id', 'slug', 'extra_attributes')
        );

        if ($validated['model_type'] === MorphMap::alias(Duty::class)) {
            $type->roles()->sync($validated['roles'] ?? []);
        }

        return redirect()->route('types.index')
            ->with('success', $this->entityMessage('created', 'type'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Type $type)
    {
        $this->handleAuthorization('view', $type);

        $relation = $type->typeableRelation();
        $type->load([
            'parent:id,title',
            'roles:id,name',
            ...($relation === null ? [] : [$relation => fn ($query) => $query->select('id', 'name')]),
        ]);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'contentType' => $type->toFullArray(),
            'attachedModels' => $relation === null ? [] : $type->{$relation}->map(fn ($model): array => [
                'id' => $model->id,
                'name' => $model->name,
            ])->values(),
            'can' => [
                'update' => auth()->user()?->can('update', $type) ?? false,
                'delete' => auth()->user()?->can('delete', $type) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Type $type)
    {
        $this->handleAuthorization('update', $type);

        // A type persisted before the allowlist existed may carry an unsupported
        // model_type; fall back to loading nothing rather than blowing up.
        $modelType = $type->typeableRelation();

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'contentType' => [
                ...($modelType === null ? $type : $type->load($modelType))->toFullArray(),
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
    public function update(UpdateTypeRequest $request, Type $type)
    {
        $validated = $request->validated();

        $type->update($request->safe()->only('title', 'model_type', 'description', 'parent_id', 'extra_attributes'));

        // Resolved through the allowlist rather than built from the request, so
        // only `institutions` and `duties` are ever reachable.
        $relation = Type::TYPEABLE_RELATIONS[$validated['model_type']];

        $type->{$relation}()->sync($validated[$relation] ?? []);

        if ($validated['model_type'] === MorphMap::alias(Duty::class)) {
            $type->roles()->sync($validated['roles'] ?? []);
        }

        return back()->with('success', $this->entityMessage('updated', 'type'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Type $type)
    {
        $this->handleAuthorization('delete', $type);

        $type->delete();

        return redirect()->route('types.index')
            ->with('success', $this->entityMessage('deleted', 'type'));
    }

    public function restore(Type $type): RedirectResponse
    {
        return $this->restoreModel($type, $this->entityMessage('restored', 'type'));
    }

    public function forceDelete(Type $type): RedirectResponse
    {
        return $this->forceDeleteModel($type);
    }
}
