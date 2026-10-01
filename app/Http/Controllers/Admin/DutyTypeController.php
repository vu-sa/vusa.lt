<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\DutyTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Requests\SyncTypeRolesRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\DutyResponsibility;
use App\Models\Role;
use App\Models\DutyType;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class DutyTypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTypeRequest $request)
    {
        $this->handleAuthorization('viewAny', DutyType::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        $types = DutyType::query()
            ->when($request->getShowDeleted(), fn ($query) => $query->onlyTrashed())
            ->orderBy('id')
            ->get();

        return $this->inertiaResponse('Admin/ModelMeta/IndexDomainTypes', [
            'typeKind' => 'dutyType',
            'types' => $this->appendForceDeleteBlockedReason($types, $request)->values(),
            'deletedCount' => DutyType::onlyTrashed()->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', DutyType::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateType', [
            'typeKind' => 'dutyType',
            'contentTypes' => DutyType::select('id', 'title')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DutyTypeRequest $request)
    {
        $type = DutyType::query()->create(
            $request->safe()->only('title', 'description', 'parent_id', 'slug')
        );

        return redirect()->route('dutyTypes.show', $type)
            ->with('success', $this->entityMessage('created', 'dutyType'));
    }

    /**
     * Display the specified resource.
     */
    public function show(DutyType $type)
    {
        $this->handleAuthorization('view', $type);

        $responsibleDuties = [];

        $relation = 'duties';
        $type->load([
            'parent:id,title',
            'roles:id,name',
            ...($relation === null ? [] : [$relation => fn ($query) => $query->select('id', 'name')]),
        ]);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'typeKind' => 'dutyType',
            'contentType' => $type->toFullArray(),
            'attachedModels' => $relation === null ? [] : $type->{$relation}->map(fn ($model): array => [
                'id' => $model->getKey(),
                'name' => $model->getAttribute('name'),
            ])->values(),
            'modelOptions' => Inertia::optional(fn () => \App\Models\Duty::query()->select(['id', 'name'])->with('tenants')->orderBy('name')->get()),
            'roleOptions' => Inertia::optional(fn () => Role::query()->orderBy('name')->get(['id', 'name'])),
            // Duties responsible for every institution of this type (e.g. VU Senatas → CB coordinator).
            'responsibleDuties' => $responsibleDuties,
            'sharepointPath' => SharepointFileService::pathOrNull($type),
            'files' => Inertia::defer(fn () => $type->availableFiles()->orderByDesc('file_date')->get(), 'files'),
            'can' => [
                'update' => auth()->user()?->can('update', $type) ?? false,
                'delete' => auth()->user()?->can('delete', $type) ?? false,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DutyType $type)
    {
        $this->handleAuthorization('update', $type);

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'typeKind' => 'dutyType',
            'contentType' => $type->toFullArray(),
            'contentTypes' => DutyType::select('id', 'title')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DutyTypeRequest $request, DutyType $type)
    {
        $type->update($request->safe()->only('title', 'description', 'parent_id'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function syncModels(SyncTypeModelsRequest $request, DutyType $type): RedirectResponse
    {
        $relation = 'duties';
        abort_if($relation === null, 403);

        $type->{$relation}()->sync($request->validated('models'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function syncRoles(SyncTypeRolesRequest $request, DutyType $type): RedirectResponse
    {
        $type->roles()->sync($request->validated('roles'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DutyType $type)
    {
        $this->handleAuthorization('delete', $type);

        $type->delete();

        return redirect()->route('dutyTypes.index')
            ->with('success', $this->entityMessage('deleted', 'dutyType'));
    }

    public function restore(DutyType $type): RedirectResponse
    {
        return $this->restoreModel($type, $this->entityMessage('restored', 'dutyType'));
    }

    public function forceDelete(DutyType $type): RedirectResponse
    {
        return $this->forceDeleteModel($type);
    }
}
