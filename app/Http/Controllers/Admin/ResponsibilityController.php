<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ForgetUserAccess;
use App\Enums\MailingListSourceType;
use App\Enums\ResponsibilityScope;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexRoleRequest;
use App\Http\Requests\StoreRoleGrantRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncRoleAttachableTypesRequest;
use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\MailingListSource;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Permissions\ResponsibilityPermissionGaps;
use App\Services\PostCreateRedirect;
use App\Support\MorphMap;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Atsakomybės: what a duty does, the permissions that work needs, and to whom it is given.
 * Spatie calls them roles; the `roles` table and the Role model hold them.
 */
class ResponsibilityController extends AdminController
{
    public function index(IndexRoleRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        // A short list sent whole: the collection searches, sorts and filters it in the browser.
        return $this->inertiaResponse('Admin/Permissions/IndexResponsibility', [
            'responsibilities' => Role::query()->exceptSuperAdmin()->withCount(['permissions', 'duties'])->get()
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'title' => $role->displayTitle(),
                    'description' => $role->getTranslation('description', app()->getLocale()) ?: null,
                    'purpose' => $role->purpose?->value,
                    'purpose_label' => $role->purpose ? (string) __($role->purpose->labelKey()) : null,
                    'permissions_count' => $role->permissions_count,
                    'duties_count' => $role->duties_count,
                    'people_count' => $role->currentUsersThroughDuties()->distinct()->count('users.id'),
                    'updated_at' => $role->updated_at?->toDateTimeString(),
                ])
                ->sortBy('title')
                ->values(),
        ]);
    }

    public function create(): Response
    {
        $this->handleAuthorization('create', Role::class);

        return $this->inertiaResponse('Admin/Permissions/CreateResponsibility');
    }

    public function store(StoreRoleRequest $request, PostCreateRedirect $redirect): RedirectResponse
    {
        $role = new Role([
            'name' => Role::generateName($request->validated('title.lt')),
            'title' => array_filter($request->validated('title')),
            'description' => array_filter($request->validated('description') ?? []),
            'guard_name' => 'web',
        ]);
        $role->save();

        return redirect($redirect->url($request->user(), $role, 'responsibilities.show', 'view', 'responsibilities.index'))
            ->with('success', $this->entityMessage('created', 'role'));
    }

    public function show(Role $role): Response
    {
        $this->handleAuthorization('view', $role);

        $role->load('permissions:id,name', 'attachable_types:id');
        $held = $role->permissions->pluck('name');

        return $this->inertiaResponse('Admin/Permissions/ShowResponsibility', [
            'responsibility' => [
                ...$role->only(['id', 'name']),
                'title' => $role->getTranslations('title'),
                'description' => $role->getTranslations('description'),
                'display_title' => $role->displayTitle(),
                'purpose' => $role->purpose?->value,
                'purpose_label' => $role->purpose ? (string) __($role->purpose->labelKey()) : null,
                'purpose_drives' => $role->purpose ? (string) __($role->purpose->drivesKey()) : null,
                'purpose_scopes' => array_map(fn (ResponsibilityScope $scope) => $scope->value, $role->purpose?->allowedScopes() ?? []),
                'is_super_admin' => $role->isSuperAdmin(),
                'updated_at' => $role->updated_at?->toDateTimeString(),
            ],
            'facts' => $this->facts($role),
            'catalog' => PermissionCatalog::toFrontend(),
            'grantedPermissions' => $held->filter(PermissionCatalog::isGrantable(...))->values(),
            'legacyPermissions' => $held->reject(PermissionCatalog::isGrantable(...))->values(),
            'attachableTypes' => $role->attachable_types->modelKeys(),
            'dutyTypeOptions' => DutyType::query()->get(['id', 'title'])
                ->map(fn (DutyType $type): array => ['id' => (int) $type->id, 'title' => (string) $type->getTranslation('title', app()->getLocale())])
                ->sortBy('title')
                ->values(),
            'grants' => Inertia::defer(fn () => $this->grants($role), 'grants'),
            'people' => Inertia::defer(fn () => $this->people($role), 'people'),
            'grantOptions' => Inertia::optional(fn () => $this->grantOptions()),
            'can' => [
                'update' => request()->user()->can('update', $role) && ! $role->isSuperAdmin(),
                'delete' => request()->user()->can('delete', $role) && $this->deleteBlockedReason($role) === null,
                'manageSettings' => Gate::allows('manage-settings'),
            ],
        ]);
    }

    public function edit(Role $role): Response|RedirectResponse
    {
        $this->handleAuthorization('update', $role);

        if ($role->isSuperAdmin()) {
            return back()->with('info', __('messages.role.not_editable'));
        }

        return $this->inertiaResponse('Admin/Permissions/EditResponsibility', [
            'responsibility' => [
                'id' => $role->id,
                'title' => $role->getTranslations('title'),
                'description' => $role->getTranslations('description'),
                'purpose_label' => $role->purpose ? (string) __($role->purpose->labelKey()) : null,
            ],
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        if ($role->isSuperAdmin()) {
            return back()->with('info', __('messages.role.not_editable'));
        }

        $role->update([
            'title' => array_filter($request->validated('title')),
            'description' => array_filter($request->validated('description') ?? []),
        ]);

        return redirect()->route('responsibilities.show', $role)->with('success', $this->entityMessage('updated', 'role'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->handleAuthorization('delete', $role);

        if ($reason = $this->deleteBlockedReason($role)) {
            return back()->with('error', $reason);
        }

        $holderIds = $this->holderIds($role);
        // One by one, so the projection takes the responsibility off every duty it reached.
        $role->grants()->get()->each->delete();
        $role->delete();

        ForgetUserAccess::execute($holderIds);

        return redirect()->route('responsibilities.index')->with('success', $this->entityMessage('deleted', 'role'));
    }

    public function updatePermissions(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $current = $role->permissions()->pluck('name')->filter(PermissionCatalog::isGrantable(...));
        $requested = collect($request->validated('permissions'));
        $added = $requested->diff($current)->values();
        $removed = $current->diff($requested)->values();
        $gaps = app(ResponsibilityPermissionGaps::class);
        $gapsBefore = count($gaps->forRole($role));

        if ($removed->isNotEmpty()) {
            $role->revokePermissionTo($removed->all());
        }

        if ($added->isNotEmpty()) {
            $role->givePermissionTo($added->all());
        }

        $role->touch();
        ForgetUserAccess::execute($this->holderIds($role));

        $message = __('responsibilities.messages.permissions_saved', ['added' => $added->count(), 'removed' => $removed->count()]);

        return count($gaps->forRole($role->fresh())) > $gapsBefore
            ? back()->with(['success' => $message, 'warning' => __('responsibilities.messages.minimum_unmet')])
            : back()->with('success', $message);
    }

    public function storeGrant(StoreRoleGrantRequest $request, Role $role): RedirectResponse
    {
        $scopeType = $request->scopeType();
        $role->grants()->firstOrCreate([
            'grantee_type' => $request->validated('grantee_type'),
            'grantee_id' => (string) $request->validated('grantee_id'),
            'scope_type' => $scopeType?->value,
            'scope_id' => $scopeType ? (string) $request->validated('scope_id') : null,
        ]);

        return back()->with('success', __('responsibilities.messages.added'));
    }

    public function destroyGrant(Request $request, Role $role, string $grant): RedirectResponse
    {
        $this->handleAuthorization('update', $role);

        // Resolved through the responsibility so a crafted id cannot reach another one's grant.
        $responsibilityGrant = $role->grants()->find($grant);

        abort_if(! $responsibilityGrant instanceof ResponsibilityGrant, 404);

        if ($warning = $this->guardSelfLockout($request->user(), $this->reachesActor($request->user(), $responsibilityGrant), $request, fn () => $responsibilityGrant->delete())) {
            return $warning;
        }

        return back()->with('success', __('responsibilities.messages.removed'));
    }

    public function syncAttachableTypes(SyncRoleAttachableTypesRequest $request, Role $role): RedirectResponse
    {
        $role->attachable_types()->sync($request->validated('attachable_types'));

        return back()->with('success', __('messages.role.attachables_updated'));
    }

    /**
     * @return array<string, int>
     */
    private function facts(Role $role): array
    {
        $grants = $role->grants()->get(['grantee_type']);

        return [
            'permissions' => $role->permissions->filter(fn ($permission) => PermissionCatalog::isGrantable($permission->name))->count(),
            'duties' => $role->duties()->count(),
            'direct' => $grants->where('grantee_type', MorphMap::alias(Duty::class))->count(),
            'via_types' => $grants->where('grantee_type', MorphMap::alias(DutyType::class))->count(),
            'via_institutions' => $grants->where('grantee_type', MorphMap::alias(Institution::class))->count(),
            'people' => $role->currentUsersThroughDuties()->distinct()->count('users.id'),
            'gaps' => count(app(ResponsibilityPermissionGaps::class)->forRole($role)),
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function grants(Role $role): array
    {
        $grants = $role->grants()
            ->with(['scope', 'grantee' => fn (MorphTo $grantee) => $grantee
                ->morphWith([Duty::class => ['institution:id,name,tenant_id', 'institution.tenant:id,shortname']])
                ->morphWithCount([DutyType::class => ['duties'], Institution::class => ['duties']])])
            ->get();
        $currentHolders = Duty::query()
            ->whereIn('id', $grants->where('grantee_type', MorphMap::alias(Duty::class))->pluck('grantee_id'))
            ->withCount('current_users')
            ->pluck('current_users_count', 'id');
        $gaps = collect(app(ResponsibilityPermissionGaps::class)->forRole($role))->keyBy(fn (array $gap) => $gap['duty']->id);

        return [
            'duties' => $grants->where('grantee_type', MorphMap::alias(Duty::class))
                ->filter(fn (ResponsibilityGrant $grant) => $grant->grantee instanceof Duty)
                ->map(fn (ResponsibilityGrant $grant): array => [
                    'id' => $grant->id,
                    'duty' => ['id' => $grant->grantee->id, 'name' => (string) $grant->grantee->getTranslation('name', app()->getLocale())],
                    'institution' => $grant->grantee->institution ? ['id' => $grant->grantee->institution->id, 'name' => (string) $grant->grantee->institution->getTranslation('name', app()->getLocale())] : null,
                    'tenant' => $grant->grantee->institution?->tenant ? (string) __($grant->grantee->institution->tenant->shortname) : null,
                    'scope_type' => $grant->scope_type,
                    'scope_name' => $this->scopeName($grant->scope),
                    'holders_count' => (int) ($currentHolders[$grant->grantee_id] ?? 0),
                    'missing_permissions' => $gaps->get($grant->grantee_id)['missing'] ?? [],
                ])
                ->sortBy(fn (array $row): string => ($row['tenant'] ?? '').($row['institution']['name'] ?? '').$row['duty']['name'])
                ->values()
                ->all(),
            'dutyTypes' => $grants->where('grantee_type', MorphMap::alias(DutyType::class))
                ->filter(fn (ResponsibilityGrant $grant) => $grant->grantee instanceof DutyType)
                ->map(fn (ResponsibilityGrant $grant): array => [
                    'id' => $grant->id,
                    'type' => ['id' => (int) $grant->grantee->id, 'title' => (string) $grant->grantee->getTranslation('title', app()->getLocale())],
                    'scope_type' => $grant->scope_type,
                    'scope_name' => $this->scopeName($grant->scope),
                    'duties_count' => (int) $grant->grantee->duties_count,
                    'needs_scope' => $grant->needsScope(),
                ])
                ->values()
                ->all(),
            'institutions' => $grants->where('grantee_type', MorphMap::alias(Institution::class))
                ->filter(fn (ResponsibilityGrant $grant) => $grant->grantee instanceof Institution)
                ->map(fn (ResponsibilityGrant $grant): array => [
                    'id' => $grant->id,
                    'institution' => ['id' => $grant->grantee->id, 'name' => (string) $grant->grantee->getTranslation('name', app()->getLocale())],
                    'scope_type' => $grant->scope_type,
                    'scope_name' => $this->scopeName($grant->scope),
                    'duties_count' => (int) $grant->grantee->duties_count,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Current holders, each with the duties that give them the responsibility.
     *
     * @return list<array<string, mixed>>
     */
    private function people(Role $role): array
    {
        return $role->duties()
            ->with(['current_users:id,name,profile_photo_path', 'institution:id,name'])
            ->get()
            ->flatMap(fn (Duty $duty) => $duty->current_users->map(fn (User $user): array => [
                'user' => $user->only(['id', 'name', 'profile_photo_path']),
                'duty' => ['id' => $duty->id, 'name' => (string) $duty->getTranslation('name', app()->getLocale()), 'institution' => (string) $duty->institution?->getTranslation('name', app()->getLocale())],
            ]))
            ->groupBy('user.id')
            ->map(fn (Collection $rows): array => [
                ...$rows->first()['user'],
                'duties' => $rows->pluck('duty')->values()->all(),
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function grantOptions(): array
    {
        return [
            'institutions' => Institution::query()->where('is_active', true)->with('tenant:id,shortname')->get(['id', 'name', 'tenant_id'])
                ->map(fn (Institution $institution): array => [
                    'id' => $institution->id,
                    'name' => trim((string) $institution->getTranslation('name', app()->getLocale()).' · '.__((string) $institution->tenant?->shortname), ' ·'),
                ])
                ->sortBy('name')
                ->values(),
            'tenants' => Tenant::query()->orderBy('shortname')->get(['id', 'shortname'])
                ->map(fn (Tenant $tenant): array => ['id' => (string) $tenant->id, 'shortname' => (string) __($tenant->shortname)]),
            'institutionTypes' => InstitutionType::query()->get(['id', 'title'])
                ->map(fn (InstitutionType $type): array => ['id' => (string) $type->id, 'title' => (string) $type->getTranslation('title', app()->getLocale())])
                ->sortBy('title')
                ->values(),
        ];
    }

    /**
     * Why the responsibility must stay: the app finds people by it, or a mailing list takes people by it.
     */
    private function deleteBlockedReason(Role $role): ?string
    {
        if ($role->isSuperAdmin()) {
            return (string) __('messages.role.not_deletable');
        }

        if ($role->purpose !== null) {
            return (string) __('responsibilities.messages.purpose_blocks_delete', ['purpose' => __($role->purpose->labelKey())]);
        }

        $lists = MailingListSource::query()
            ->where('type', MailingListSourceType::Responsibility)
            ->where('source_id', strtolower($role->id))
            ->with('mailingList:id,address')
            ->get()
            ->pluck('mailingList.address')
            ->filter()
            ->unique();

        return $lists->isEmpty() ? null : (string) __('responsibilities.messages.list_blocks_delete', ['lists' => $lists->implode(', ')]);
    }

    private function reachesActor(User $actor, ResponsibilityGrant $grant): bool
    {
        if ($actor->isSuperAdmin()) {
            return false;
        }

        $duties = $actor->current_duties()->with('types:id')->get(['duties.id', 'duties.institution_id']);

        return match ($grant->grantee_type) {
            MorphMap::alias(Duty::class) => $duties->contains('id', $grant->grantee_id),
            MorphMap::alias(DutyType::class) => $duties->contains(fn (Duty $duty) => $duty->types->contains('id', (int) $grant->grantee_id)),
            MorphMap::alias(Institution::class) => $duties->contains('institution_id', $grant->grantee_id),
            default => false,
        };
    }

    /**
     * @return Collection<int, string>
     */
    private function holderIds(Role $role): Collection
    {
        return $role->usersThroughDuties()->pluck('users.id')
            ->merge($role->users()->pluck('users.id'))
            ->map(strval(...))
            ->unique()
            ->values();
    }

    private function scopeName(mixed $scope): ?string
    {
        return match (true) {
            $scope instanceof Tenant => (string) __($scope->shortname),
            $scope instanceof InstitutionType => (string) $scope->getTranslation('title', app()->getLocale()),
            $scope instanceof Institution => (string) $scope->getTranslation('name', app()->getLocale()),
            default => null,
        };
    }
}
