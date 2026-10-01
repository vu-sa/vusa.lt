<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\InstitutionTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Requests\SyncTypeRolesRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\DutyResponsibility;
use App\Models\Role;
use App\Models\InstitutionType;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class InstitutionTypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTypeRequest $request)
    {
        $this->handleAuthorization('viewAny', InstitutionType::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        $types = InstitutionType::query()
            ->when($request->getShowDeleted(), fn ($query) => $query->onlyTrashed())
            ->orderBy('id')
            ->get();

        return $this->inertiaResponse('Admin/ModelMeta/IndexDomainTypes', [
            'typeKind' => 'institutionType',
            'types' => $this->appendForceDeleteBlockedReason($types, $request)->values(),
            'deletedCount' => InstitutionType::onlyTrashed()->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', InstitutionType::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateType', [
            'typeKind' => 'institutionType',
            'contentTypes' => InstitutionType::select('id', 'title')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InstitutionTypeRequest $request)
    {
        $type = InstitutionType::query()->create(
            $request->safe()->only('title', 'description', 'parent_id', 'slug', 'extra_attributes')
        );

        return redirect()->route('institutionTypes.show', $type)
            ->with('success', $this->entityMessage('created', 'institutionType'));
    }

    /**
     * Display the specified resource.
     */
    public function show(InstitutionType $type)
    {
        $this->handleAuthorization('view', $type);

        $responsibleDuties = [];

        foreach (DutyResponsibility::query()
            ->where('scope_type', ResponsibilityScope::InstitutionType)
            ->where('scope_id', (string) $type->id)
            ->with('duty:id,name')
            ->get() as $assignment) {
            $duty = $assignment->duty;

            if ($duty === null) {
                continue;
            }

            $responsibleDuties[] = [
                'id' => $assignment->id,
                'duty_id' => $assignment->duty_id,
                'duty' => (string) $duty->name,
                'label' => (string) __($assignment->responsibility->labelKey()),
            ];
        }

        $relation = 'institutions';
        $type->load([
            'parent:id,title',
            ...($relation === null ? [] : [$relation => fn ($query) => $query->select('id', 'name')]),
        ]);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'typeKind' => 'institutionType',
            'contentType' => $type->toFullArray(),
            'attachedModels' => $relation === null ? [] : $type->{$relation}->map(fn ($model): array => [
                'id' => $model->getKey(),
                'name' => $model->getAttribute('name'),
            ])->values(),
            'modelOptions' => Inertia::optional(fn () => \App\Models\Institution::query()->select(['id', 'name'])->with('tenants')->orderBy('name')->get()),
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
    public function edit(InstitutionType $type)
    {
        $this->handleAuthorization('update', $type);

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'typeKind' => 'institutionType',
            'contentType' => $type->toFullArray(),
            'contentTypes' => InstitutionType::select('id', 'title')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InstitutionTypeRequest $request, InstitutionType $type)
    {
        $type->update($request->safe()->only('title', 'description', 'parent_id', 'slug', 'extra_attributes'));

        return back()->with('success', $this->entityMessage('updated', 'institutionType'));
    }

    public function syncModels(SyncTypeModelsRequest $request, InstitutionType $type): RedirectResponse
    {
        $relation = 'institutions';
        abort_if($relation === null, 403);

        $type->{$relation}()->sync($request->validated('models'));

        return back()->with('success', $this->entityMessage('updated', 'institutionType'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InstitutionType $type)
    {
        $this->handleAuthorization('delete', $type);

        $type->delete();

        return redirect()->route('institutionTypes.index')
            ->with('success', $this->entityMessage('deleted', 'institutionType'));
    }

    public function restore(InstitutionType $type): RedirectResponse
    {
        return $this->restoreModel($type, $this->entityMessage('restored', 'institutionType'));
    }

    public function forceDelete(InstitutionType $type): RedirectResponse
    {
        return $this->forceDeleteModel($type);
    }
}
