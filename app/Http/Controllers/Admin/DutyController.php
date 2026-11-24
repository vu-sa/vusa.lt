<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildDutyIndexQuery;
use App\Actions\Duties\CreateDuty;
use App\Actions\Duties\GetAssignableExOfficioDuties;
use App\Actions\GetAttachableTypesForDuty;
use App\Actions\GetTenantsForUpserts;
use App\Actions\MergeDuties;
use App\Actions\UpdateDuty;
use App\Http\Controllers\AdminController;
use App\Http\Requests\BatchUpdateDutyUsersRequest;
use App\Http\Requests\CreateDutyRequest;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Requests\MergeDutiesRequest;
use App\Http\Requests\StoreDutyRequest;
use App\Http\Requests\UpdateDutyRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Pivots\Dutiable;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ContactPresentationService;
use App\Services\Duties\DutyAssignmentService;
use App\Services\DutyAccessService;
use App\Services\DutyPanelPresenter;
use App\Services\DutyPresenter;
use App\Services\DutyResponsibilityPresenter;
use App\Services\Institutions\InstitutionQueries;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\PostCreateRedirect;
use App\Services\TanstackTableService;
use App\Support\MorphMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DutyController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService, private DutyAssignmentService $assignments) {}

    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Duty::class)]
    public function index(IndexDutyRequest $request, DutyAccessService $access): Response
    {
        $query = $this->applyTanstackFilters(
            BuildDutyIndexQuery::execute($request, $access),
            $request,
            $this->tableService,
            ['name', 'email'],
        );

        $deletedCount = $this->getTrashedCount($query);

        $duties = $query->paginate($request->getPerPage())
            ->withQueryString();

        $this->appendForceDeleteBlockedReason($duties->getCollection(), $request);

        return Inertia::render('Admin/People/IndexDuty', [
            'duties' => [
                'data' => $duties->getCollection()->map(fn (Duty $duty): array => DutyPresenter::forIndex($duty, $request->user()))->values(),
                'meta' => [
                    'total' => $duties->total(),
                    'per_page' => $duties->perPage(),
                    'current_page' => $duties->currentPage(),
                    'last_page' => $duties->lastPage(),
                    'from' => $duties->firstItem(),
                    'to' => $duties->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
            'mergePreset' => $request->mergePreset(),
            'dutyTypes' => DutyType::query()->get(['id', 'title'])->map->toFullArray()->values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Duty::class)]
    public function create(CreateDutyRequest $request)
    {
        $actor = $request->user();

        return Inertia::render('Admin/People/CreateDuty', [
            'dutyTypes' => GetAttachableTypesForDuty::execute()->values(),
            'assignableInstitutions' => InstitutionQueries::getInstitutionsForUpserts($actor, $this->authorizer),
            'prefillInstitutionId' => $request->validated('institution_id'),
            'assignableTenants' => GetTenantsForUpserts::execute($actor, 'duties.create.padalinys', $this->authorizer),
            'assignableDuties' => GetAssignableExOfficioDuties::execute($actor, $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Duty::class)]
    public function store(StoreDutyRequest $request, CreateDuty $create, PostCreateRedirect $redirect)
    {
        $duty = $create->execute($request->validated(), $request->user());

        return redirect($redirect->url($request->user(), $duty, 'duties.show', 'view', 'duties.index'))
            ->with('success', $this->entityMessage('created', 'duty'));
    }

    /**
     * Display the specified resource.
     */
    #[Authorize('view', 'duty')]
    public function show(Duty $duty)
    {
        $loadDuty = fn () => once(function () use ($duty): Duty {
            $duty->loadMissing([
                'institution.tenant',
                'users' => fn ($query) => $query->withPivot('created_at', 'updated_at'),
                'types',
                'formerNames',
            ]);

            return $duty->setRelation('users', app(ContactPresentationService::class)->sortHolders($duty, $duty->users));
        });
        $user = fn () => once(fn () => request()->user());

        return Inertia::render('Admin/People/ShowDuty', [
            'duty' => fn (): array => DutyPresenter::forShow($loadDuty()),
            'files' => Inertia::defer(fn () => DutyPanelPresenter::files($duty->availableFiles()->orderByDesc('file_date')->get()), 'files'),
            'can' => fn (): array => DutyPanelPresenter::abilities($duty, $user()),
            'delegatedSeatTenants' => fn () => DutyPanelPresenter::delegatedSeatTenants($loadDuty(), $user()),
            'otherDuties' => Inertia::defer(fn () => DutyPanelPresenter::otherDuties($duty), 'dutyPanels'),
            'studyPrograms' => Inertia::defer(fn () => DutyPanelPresenter::studyPrograms($this->studyProgramsFor($duty)), 'dutyPanels'),
            'lineage' => Inertia::defer(fn () => DutyPanelPresenter::lineage($duty, $user()), 'dutyPanels'),
            'responsibilities' => Inertia::defer(fn () => DutyResponsibilityPresenter::payload($duty), 'dutyPanels'),
            'quotaTenants' => Inertia::defer(fn () => DutyPanelPresenter::quotaTenants($duty), 'dutyPanels'),
            // Only the add sheet needs these; loaded when it opens.
            'responsibilityOptions' => Inertia::optional(function () use ($user, $duty) {
                abort_unless($user()?->can('update', $duty), 403);

                return DutyResponsibilityPresenter::options($user());
            }),
        ]);
    }

    /**
     * Study programs of the duty's own tenant, for the assignment sheet's picker.
     * Falls back to every programme when the tenant cannot be resolved.
     *
     * @return Collection<int, StudyProgram>
     */
    private function studyProgramsFor(Duty $duty): Collection
    {
        $tenantId = $duty->institution?->tenant_id;
        // A programme already on an assignment stays selectable even from another tenant.
        $inUse = $duty->users->map(fn (User $user) => $user->pivot?->study_program_id)->filter()->all();

        return StudyProgram::query()
            ->when($tenantId, fn ($query) => $query->where(
                fn ($query) => $query->where('tenant_id', $tenantId)->orWhereIn('id', $inUse)
            ))
            ->get();
    }

    /**
     * Show the form for editing the specified resource.
     */
    // Owning-tenant admins (can update the duty) and cross-tenant admins
    // (can only manage their tenant's reps) may both open this page.
    #[Authorize('managePeople', 'duty')]
    public function edit(Duty $duty)
    {
        $user = request()->user();
        $canEditDuty = $user->can('update', $duty);

        $actingAssignableTenantIds = $canEditDuty
            ? collect()
            : $this->authorizer->tenants($user, 'duties.update.padalinys')->pluck('id');

        $baseRelations = ['institution', 'types', 'exOfficioTargetDuties', 'assignableTenants'];

        // Owning admin sees all current_users (with pivot tenant_id for display split).
        // Cross-tenant admin sees only reps with their tenant_id on the dutiable row.
        $currentUsersLoad = $canEditDuty
            ? ['current_users' => fn ($q) => $q->withPivot('tenant_id')]
            : ['current_users' => fn ($q) => $q->withPivot('tenant_id')
                ->wherePivotIn('tenant_id', $actingAssignableTenantIds->all())];

        $duty->load(array_merge($baseRelations, $currentUsersLoad));

        if (! $canEditDuty) {
            $duty->setRelation('assignableTenants', $duty->assignableTenants->whereIn('id', $actingAssignableTenantIds)->values());
        }

        // Build a map { tenantId => [userId, ...] } for active cross-tenant reps so the
        // UI can pre-populate each assignable-tenant's user picker.
        // Must match Duty::current_users() semantics: the end date is the last day in
        // office, so a rep whose end_date is today is still active.
        // Ex-officio rows are left out — they are not the picker's to grant or revoke.
        $crossTenantRepsQuery = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNotNull('tenant_id')
            ->whereNull('via_dutiable_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            });

        if (! $canEditDuty) {
            $crossTenantRepsQuery->whereIn('tenant_id', $actingAssignableTenantIds->all());
        }

        $assignableTenantUsers = $crossTenantRepsQuery
            ->get(['dutiable_id', 'tenant_id'])
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->pluck('dutiable_id')->values()->all());

        return Inertia::render('Admin/People/EditDuty', [
            'duty' => [
                ...$duty->toFullArray(),
                // Edited in both languages, so the localized relation serialization will not do.
                'former_names' => $duty->formerNames
                    ->map(fn (DutyName $former) => ['name' => $former->getTranslations('name'), 'used_until' => $former->used_until->toDateString()])
                    ->values()
                    ->all(),
            ],
            'canEditDuty' => $canEditDuty,
            'actingAssignableTenantIds' => $actingAssignableTenantIds->values()->all(),
            'assignableTenantUsers' => $assignableTenantUsers,
            'exOfficioMembers' => $this->exOfficioMembersForDutyForm($duty, $canEditDuty ? null : $actingAssignableTenantIds),
            'dutyTypes' => GetAttachableTypesForDuty::execute()->values(),
            'assignableInstitutions' => InstitutionQueries::getInstitutionsForUpserts($user, $this->authorizer),
            'assignableTenants' => GetTenantsForUpserts::execute($user, 'duties.update.padalinys', $this->authorizer),
            'assignableDuties' => $canEditDuty ? GetAssignableExOfficioDuties::execute($user, $this->authorizer, $duty) : collect(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'duty')]
    public function update(UpdateDutyRequest $request, Duty $duty, UpdateDuty $updateDuty): RedirectResponse
    {
        $actor = $request->user();

        $data = $request->validated();
        $mutation = fn () => $updateDuty->execute($duty, $data, $actor);

        // The acting user holding this duty may lose access if its roles, types
        // or institution change beneath them.
        $couldAffectSelf = ! $actor->isSuperAdmin()
            && Dutiable::where('duty_id', $duty->id)
                ->where('dutiable_type', MorphMap::alias(User::class))
                ->where('dutiable_id', $actor->id)
                ->where(function ($query): void {
                    $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
                })
                ->exists();

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', $this->entityMessage('updated', 'duty'));
    }

    /**
     * Active ex-officio seats on this duty, for display in the edit form.
     *
     * These rows are granted by another duty and end with it, so no picker may
     * grant or revoke them — but they do occupy a seat and count against the
     * assigning tenant's quota, which is why the form still needs to see them.
     * Without this a tenant with 3 of 3 seats filled (one ex officio) reads as 2/3
     * and looks like it has room left.
     *
     * @param  Collection<int, int>|null  $limitToTenantIds  Null = no tenant limit (owning admin).
     * @return array<int, array{dutiable_id: string, user_id: string, name: string, profile_photo_path: string|null, tenant_id: int|null, source_duty_name: string|null}>
     */
    private function exOfficioMembersForDutyForm(Duty $duty, ?Collection $limitToTenantIds): array
    {
        $query = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereNotNull('via_dutiable_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            });

        if ($limitToTenantIds !== null) {
            $query->whereIn('tenant_id', $limitToTenantIds->all());
        }

        return $query
            ->with(['user:id,name,profile_photo_path', 'viaDutiable.duty:id,name'])
            ->get()
            // A soft-deleted holder leaves the row without a user to name.
            ->filter(fn (Dutiable $row) => $row->user !== null)
            ->map(fn (Dutiable $row) => [
                'dutiable_id' => $row->id,
                'user_id' => $row->dutiable_id,
                'name' => $row->user->name,
                'profile_photo_path' => $row->user->profile_photo_path,
                'tenant_id' => $row->tenant_id,
                'source_duty_name' => $row->viaDutiable?->duty?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'duty')]
    public function destroy(Duty $duty)
    {
        $duty->delete();

        return redirect()->route('duties.index')->with('info', $this->entityMessage('deleted', 'duty'));
    }

    #[Authorize('restore', 'duty')]
    public function restore(Duty $duty): RedirectResponse
    {
        return $this->restoreModel($duty);
    }

    /**
     * Merge one or more duplicate duties into a single kept duty.
     */
    #[Authorize('merge', Duty::class)]
    public function mergeDuties(MergeDutiesRequest $request)
    {
        $kept = Duty::findOrFail($request->validated('target_duty_id'));
        $sources = Duty::query()->whereIn('id', $request->validated('source_duty_ids'))->get();

        $summary = MergeDuties::execute($kept, $sources, $request->user());

        return redirect()->route('duties.edit', $kept)->with('success', trans('forms.merge_duty_summary', [
            'assignments' => $summary['moved_assignments'],
            'collapsed' => $summary['collapsed_assignments'],
            'types' => $summary['moved_types'],
            'roles' => $summary['moved_roles'],
            'exOfficio' => $summary['moved_ex_officio'],
            'quotas' => $summary['moved_tenant_quotas'],
        ]));
    }

    /**
     * Display the duty user update wizard page.
     */
    #[Authorize('viewAny', Duty::class)]
    public function updateUsersWizard(Request $request)
    {
        $actor = $request->user();
        $currentUsersLoad = ['current_users' => function ($q): void {
            $q->select('users.id', 'name', 'email', 'profile_photo_path')
                ->withPivot('start_date', 'end_date');
        }];

        // Get institutions the user can access, with their duties and current users
        // Include pivot data (start_date) to detect long-staying users
        $institutions = InstitutionQueries::getInstitutionsForUpserts($actor, $this->authorizer)
            // Inactive duties keep their history but are not staffed any more.
            ->load(['duties' => function ($query) use ($currentUsersLoad): void {
                $query->where('is_active', true)->with($currentUsersLoad);
            }, 'tenant:id,shortname']);

        // Also surface cross-tenant duties the user's tenant may assign reps to.
        // Each such institution is loaded with ONLY the assignable duty(ies),
        // carrying the `assignableTenants` pivot (quota) for the acting tenant.
        $adminReadTenantIds = $this->authorizer
            ->tenants($actor, 'duties.read.padalinys')->pluck('id');

        if ($adminReadTenantIds->isNotEmpty()) {
            // For cross-tenant duties the acting tenant only assigns *into*, show
            // only the reps explicitly assigned for the acting tenant (by tenant_id column).
            $tenantScopedCurrentUsers = ['current_users' => function ($q) use ($adminReadTenantIds): void {
                $q->select('users.id', 'name', 'email', 'profile_photo_path')
                    ->wherePivotIn('tenant_id', $adminReadTenantIds->all())
                    ->withPivot('start_date', 'end_date', 'tenant_id');
            }];

            $crossTenantInstitutions = Institution::select('id', 'name', 'alias', 'tenant_id')
                ->whereHas('duties.assignableTenants', fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds))
                ->whereNotIn('id', $institutions->pluck('id'))
                ->with([
                    'tenant:id,shortname',
                    'duties' => function ($query) use ($tenantScopedCurrentUsers, $adminReadTenantIds): void {
                        $query->where('is_active', true)
                            ->whereHas('assignableTenants', fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds))
                            ->with($tenantScopedCurrentUsers)
                            ->with(['assignableTenants' => fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds)]);
                    },
                ])
                ->get();

            $crossTenantInstitutions->each(fn (Institution $i) => $i->setAttribute('is_external', true));

            $institutions = $institutions->concat($crossTenantInstitutions);
        }

        // Get data needed for creating institutions and duties
        $assignableTenants = GetTenantsForUpserts::execute($actor, 'institutions.create.padalinys', $this->authorizer);
        $institutionTypes = InstitutionType::query()->get();

        return Inertia::render('Admin/People/DutyUserUpdateWizard', [
            // Immediate data for Step 1
            // The wizard reads logo_url; it comes from the logo's media now.
            'institutions' => $institutions->load('media')->each(
                fn (Institution $institution) => $institution->setAttribute('logo_url', $institution->imageData('logo')['thumb'] ?? null)->unsetRelation('media'),
            ),
            // Data for inline institution creation (small datasets, load immediately)
            'assignableTenants' => $assignableTenants,
            'institutionTypes' => $institutionTypes,
            // Lazy loaded data - only fetched when explicitly requested via router.reload({ only: [...] })
            // Step 3 searches users through api.v1.admin.users.search rather than
            // receiving them as a prop — this used to ship every user in the system,
            // name and email, to any admin who reached that step.
            'studyPrograms' => Inertia::optional(fn () => StudyProgram::select('id', 'name', 'degree', 'tenant_id')->get()),
            // Step 2: Duty creation (only needed if user wants to create a new duty)
            'dutyTypes' => Inertia::optional(fn () => GetAttachableTypesForDuty::execute()->values()),
        ]);
    }

    /**
     * Batch update users for a duty.
     */
    // Authorized in BatchUpdateDutyUsersRequest: the duty and permission to create users.
    public function batchUpdateUsers(BatchUpdateDutyUsersRequest $request, Duty $duty)
    {
        $validated = $request->validated();

        $actingTenantId = $request->actingTenantId();

        $actor = $request->user();
        $mutation = fn () => DB::transaction(function () use ($validated, $duty, $actingTenantId, $actor): void {
            $this->assignments->lockDuty($duty);
            $createdUsers = [];
            foreach ($validated['new_users'] ?? [] as $newUserData) {
                $user = User::create([
                    'name' => $newUserData['name'],
                    'email' => $newUserData['email'] ?? null,
                    'phone' => $newUserData['phone'] ?? null,
                ]);
                $createdUsers[$newUserData['temp_id']] = $user;
            }

            $memberIds = collect($validated['user_changes'])->pluck('user_id')
                ->map(fn (string $id) => $createdUsers[$id]->id ?? $id)->unique()->all();
            $members = User::whereKey($memberIds)->get();
            $this->assignments->auditMemberships(collect([$duty]), $members, function () use ($validated, $duty, $actingTenantId, $actor, $createdUsers): void {
                $allocatedSeat = false;
                foreach ($validated['user_changes'] as $index => $change) {
                    $dateAttribute = "user_changes.$index.end_date";
                    $userId = $change['user_id'];
                    $attributes = [
                        'start_date' => $change['start_date'] ?? null,
                        'end_date' => $change['end_date'] ?? null,
                        'study_program_id' => $change['study_program_id'] ?? null,
                    ];

                    if (str_starts_with($userId, 'new-')) {
                        if ($change['action'] !== 'add' || ! isset($createdUsers[$userId])) {
                            continue;
                        }

                        $userId = $createdUsers[$userId]->id;
                    }

                    $currentTerms = $this->assignments->manualTerms($duty->id, $userId, $actingTenantId)->notEnded();

                    if ($change['action'] === 'remove') {
                        $this->assignments->endTerms($currentTerms, $attributes['end_date'], $dateAttribute);

                        continue;
                    }

                    if ($existingTerm = $currentTerms->orderBy('start_date')->orderBy('id')->lockForUpdate()->first()) {
                        $edit = array_intersect_key($attributes, $change);
                        $edit['start_date'] ??= $existingTerm->start_date->toDateString();
                        $endDate = array_key_exists('end_date', $edit) ? $edit['end_date'] : $existingTerm->end_date?->toDateString();
                        $this->assignments->validateTermEdit($existingTerm, $edit['start_date'], $endDate, $actingTenantId, $dateAttribute);
                        $existingTerm->update($edit);
                    } else {
                        $term = $this->assignments->startTerm($duty, $userId, $actingTenantId, $attributes, audit: false, dateAttribute: $dateAttribute);
                        $allocatedSeat = $allocatedSeat || $term->end_date === null || $term->end_date->gte(today());
                    }
                }

                if ($allocatedSeat) {
                    $this->assignments->enforceTenantQuota($duty, $actingTenantId, $actor, 'user_changes');
                }
            }, actor: $actor);
        });

        // Removing themselves from this duty may strip the acting user's own access.
        $couldAffectSelf = ! $actor->isSuperAdmin()
            && collect($validated['user_changes'])
                ->contains(fn ($change) => (string) $change['user_id'] === (string) $actor->id);

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return redirect()->route('duties.show', $duty)
            ->with('success', __('dutiables.assign.members_updated'));
    }

    #[Authorize('forceDelete', 'duty')]
    public function forceDelete(Duty $duty): RedirectResponse
    {
        return $this->forceDeleteModel($duty);
    }
}
