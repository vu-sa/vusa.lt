<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\InstitutionTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Services\ResourceServices\SharepointFileService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionTypeController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function index(IndexTypeRequest $request): Response
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

    public function create(): Response
    {
        $this->handleAuthorization('create', InstitutionType::class);

        return $this->inertiaResponse('Admin/ModelMeta/CreateType', [
            'typeKind' => 'institutionType',
            'contentTypes' => InstitutionType::select('id', 'title')->get(),
        ]);
    }

    public function store(InstitutionTypeRequest $request): RedirectResponse
    {
        $type = InstitutionType::query()->create(
            $request->safe()->only('title', 'description', 'parent_id', 'slug', 'extra_attributes')
        );

        return redirect()->route('institutionTypes.show', $type)
            ->with('success', $this->entityMessage('created', 'institutionType'));
    }

    public function show(InstitutionType $type): Response
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

        $type->load([
            'parent:id,title',
            'institutions' => fn ($query) => $query->select('id', 'name'),
        ]);

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'typeKind' => 'institutionType',
            'contentType' => $type->toFullArray(),
            'attachedModels' => $type->institutions->map(fn ($model): array => [
                'id' => $model->getKey(),
                'name' => $model->getAttribute('name'),
            ])->values(),
            'modelOptions' => Inertia::optional(fn () => Institution::query()->select(['id', 'name'])->with('tenants')->orderBy('name')->get()),
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

    public function edit(InstitutionType $type): Response
    {
        $this->handleAuthorization('update', $type);

        return $this->inertiaResponse('Admin/ModelMeta/EditType', [
            'typeKind' => 'institutionType',
            'contentType' => $type->toFullArray(),
            'contentTypes' => InstitutionType::select('id', 'title')->get(),
        ]);
    }

    public function update(InstitutionTypeRequest $request, InstitutionType $type): RedirectResponse
    {
        $type->update($request->safe()->only('title', 'description', 'parent_id', 'slug', 'extra_attributes'));

        return back()->with('success', $this->entityMessage('updated', 'institutionType'));
    }

    public function syncModels(SyncTypeModelsRequest $request, InstitutionType $type): RedirectResponse
    {
        $type->institutions()->sync($request->validated('models'));

        return back()->with('success', $this->entityMessage('updated', 'institutionType'));
    }

    public function destroy(InstitutionType $type): RedirectResponse
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
