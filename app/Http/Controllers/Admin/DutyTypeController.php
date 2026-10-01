<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\DutyTypeRequest;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Requests\SyncTypeRolesRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Role;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DutyTypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function index(IndexTypeRequest $request): Response
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

    public function create(): Response
    {
        $this->handleAuthorization('create', DutyType::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateType', [
            'typeKind' => 'dutyType',
            'contentTypes' => DutyType::select('id', 'title')->get(),
        ]);
    }

    public function store(DutyTypeRequest $request): RedirectResponse
    {
        $type = DutyType::query()->create(
            $request->safe()->only('title', 'description', 'parent_id', 'slug')
        );

        return redirect()->route('dutyTypes.show', $type)
            ->with('success', $this->entityMessage('created', 'dutyType'));
    }

    public function show(DutyType $type): Response
    {
        $this->handleAuthorization('view', $type);

        $type->load([
            'parent:id,title',
            'roles:id,name',
            'duties' => fn ($query) => $query->select('id', 'name'),
        ]);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'typeKind' => 'dutyType',
            'contentType' => $type->toFullArray(),
            'attachedModels' => $type->duties->map(fn ($model): array => [
                'id' => $model->getKey(),
                'name' => $model->getAttribute('name'),
            ])->values(),
            'modelOptions' => Inertia::optional(fn () => Duty::query()->select(['id', 'name'])->with('tenants')->orderBy('name')->get()),
            'roleOptions' => Inertia::optional(fn () => Role::query()->orderBy('name')->get(['id', 'name'])),
            'responsibleDuties' => [],
            'sharepointPath' => SharepointFileService::pathOrNull($type),
            'files' => Inertia::defer(fn () => $type->availableFiles()->orderByDesc('file_date')->get(), 'files'),
            'can' => [
                'update' => auth()->user()?->can('update', $type) ?? false,
                'delete' => auth()->user()?->can('delete', $type) ?? false,
            ],
        ]);
    }

    public function edit(DutyType $type): Response
    {
        $this->handleAuthorization('update', $type);

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'typeKind' => 'dutyType',
            'contentType' => $type->toFullArray(),
            'contentTypes' => DutyType::select('id', 'title')->get(),
        ]);
    }

    public function update(DutyTypeRequest $request, DutyType $type): RedirectResponse
    {
        $type->update($request->safe()->only('title', 'description', 'parent_id', 'slug'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function syncModels(SyncTypeModelsRequest $request, DutyType $type): RedirectResponse
    {
        $type->duties()->sync($request->validated('models'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function syncRoles(SyncTypeRolesRequest $request, DutyType $type): RedirectResponse
    {
        $type->roles()->sync($request->validated('roles'));

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function destroy(DutyType $type): RedirectResponse
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
