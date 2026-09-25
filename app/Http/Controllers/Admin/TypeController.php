<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\StoreTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Requests\SyncTypeRolesRequest;
use App\Http\Requests\UpdateTypeRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Role;
use App\Models\Type;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

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
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTypeRequest $request)
    {
        $type = Type::query()->create(
            $request->safe()->only('title', 'model_type', 'description', 'parent_id', 'slug', 'extra_attributes')
        );

        return redirect()->route('types.show', $type)
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
            'modelOptions' => Inertia::optional(fn () => $type->allModelsFromModelType()),
            'roleOptions' => Inertia::optional(fn () => Role::query()->orderBy('name')->get(['id', 'name'])),
            'sharepointPath' => $type->sharepoint_path(),
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

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'contentType' => $type->toFullArray(),
            'contentTypes' => Type::select('id', 'title', 'model_type')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTypeRequest $request, Type $type)
    {
        $type->update($request->safe()->only('title', 'model_type', 'description', 'parent_id', 'extra_attributes'));

        return back()->with('success', $this->entityMessage('updated', 'type'));
    }

    public function syncModels(SyncTypeModelsRequest $request, Type $type): RedirectResponse
    {
        $relation = $type->typeableRelation();
        abort_if($relation === null, 403);

        $type->{$relation}()->sync($request->validated('models'));

        return back()->with('success', $this->entityMessage('updated', 'type'));
    }

    public function syncRoles(SyncTypeRolesRequest $request, Type $type): RedirectResponse
    {
        $type->roles()->sync($request->validated('roles'));

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
