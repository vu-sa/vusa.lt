<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Responsibilities\AssignableDutyTypes;
use App\Http\Controllers\AdminController;
use App\Http\Requests\DutyTypeRequest;
use App\Http\Requests\IndexTypeRequest;
use App\Http\Requests\SyncTypeModelsRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
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
            'responsibilityGrants.role',
            'responsibilityGrants.scope',
            'duties' => fn ($query) => $query->select('id', 'name'),
        ]);

        $actor = request()->user();
        $canManageAssignments = $actor->can('update', $type)
            && AssignableDutyTypes::allowsChanges($actor, [], [$type->id])
            && collect(['duties.update.*', 'duties.update.padalinys', 'duties.update.own'])
                ->contains(fn (string $permission): bool => app(ModelAuthorizer::class)->allows($actor, $permission));

        return $this->inertiaResponse('Admin/ModelMeta/ShowType', [
            'typeKind' => 'dutyType',
            'contentType' => [
                ...$type->withoutRelations()->toFullArray(),
                'parent' => $type->parent?->toFullArray(),
            ],
            'responsibilityGrants' => $type->responsibilityGrants->map(fn (ResponsibilityGrant $grant): array => [
                'id' => $grant->id,
                'title' => $grant->role->displayTitle(),
                'href' => $actor->can('view', $grant->role) ? route('responsibilities.show', $grant->role) : null,
                'scope_type' => $grant->scope_type,
                'scope_name' => match (true) {
                    $grant->scope instanceof Tenant => (string) __($grant->scope->shortname),
                    $grant->scope instanceof InstitutionType => (string) $grant->scope->getTranslation('title', app()->getLocale()),
                    $grant->scope instanceof Institution => (string) $grant->scope->getTranslation('name', app()->getLocale()),
                    default => null,
                },
                'needs_scope' => $grant->needsScope(),
            ])->values(),
            'attachedModels' => $type->duties->map(fn ($model): array => [
                'id' => $model->getKey(),
                'name' => $model->getAttribute('name'),
            ])->values(),
            'modelOptions' => Inertia::optional(fn () => $canManageAssignments
                ? Duty::query()->with('tenants')->get()
                    ->map(fn (Duty $duty): array => [
                        'id' => $duty->id, 'name' => (string) $duty->getTranslation('name', app()->getLocale()),
                        'can_change' => $actor->can('update', $duty),
                    ])
                    ->filter(fn (array $duty): bool => $duty['can_change'] || $type->duties->contains('id', $duty['id']))
                    ->sortBy('name')->values()
                : []),
            'responsibleDuties' => [],
            'sharepointPath' => SharepointFileService::pathOrNull($type),
            'sharepointFolderUrl' => SharepointFileService::folderUrlOrNull($type),
            'files' => Inertia::defer(fn () => $type->availableFiles()->orderByDesc('file_date')->get(), 'files'),
            'can' => [
                'update' => auth()->user()?->can('update', $type) ?? false,
                'delete' => auth()->user()?->can('delete', $type) ?? false,
                'manageAssignments' => $canManageAssignments,
                'manageResponsibilities' => $actor->can('viewAny', Role::class),
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
        $type->duties()->sync([
            ...$request->validated('models'),
            ...$type->duties()->onlyTrashed()->pluck('duties.id')->all(),
        ]);

        return back()->with('success', $this->entityMessage('updated', 'dutyType'));
    }

    public function destroy(DutyType $type): RedirectResponse
    {
        $this->handleAuthorization('delete', $type);

        if ($reason = $type->deleteBlockedReason()) {
            return back()->with('error', $reason);
        }

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
