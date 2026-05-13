<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BackfillExOfficioTargetDuty;
use App\Actions\GetAttachableTypesForDuty;
use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\BatchUpdateDutyUsersRequest;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Requests\StoreDutyRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ResourceServices\DutyService;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class DutyController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexDutyRequest $request)
    {
        $this->handleAuthorization('viewAny', Duty::class);

        $query = Duty::query()->with([
            'institution:id,name,short_name,tenant_id',
            'institution.tenant:id,shortname',
            'types:id,title',
        ]);

        $searchableColumns = ['name', 'email'];

        // Search / sort / column filters / soft-delete only — tenant scoping is
        // applied below so the assignable-tenants alternative is ORed with the
        // read scope inside one group (not appended after the search filters).
        $query = $this->applyTanstackFilters($query, $request, $this->tableService, $searchableColumns);

        $authorizer = $this->authorizer->forUser($request->user());
        $hasGlobalReadScope = $authorizer->check('duties.read.*') || $request->user()?->isSuperAdmin();

        if (! $hasGlobalReadScope) {
            $adminTenantIds = $authorizer->getTenants('duties.read.padalinys')->pluck('id')->all();
            // Cross-tenant duties (the user's tenant is in assignableTenants) are
            // included by default; the `show_external` table filter hides them.
            $includeExternal = ($request->getFilters()['show_external'] ?? true) !== false;

            $query->where(function ($q) use ($adminTenantIds, $includeExternal) {
                $q->whereHas('institution.tenant', fn ($t) => $t->whereIn('id', $adminTenantIds));

                if ($includeExternal) {
                    $q->orWhereHas('assignableTenants', fn ($a) => $a->whereIn('tenants.id', $adminTenantIds));
                }
            });
        }

        $duties = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/People/IndexDuty', [
            'duties' => [
                'data' => $duties->getCollection()->map(function ($duty) {
                    /** @var Duty $duty */
                    return $duty->toFullArray();
                })->values(),
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
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->handleAuthorization('create', Duty::class);

        return $this->inertiaResponse('Admin/People/CreateDuty', [
            'dutyTypes' => Type::where('model_type', Duty::class)->get(),
            'roles' => Role::all(),
            'assignableInstitutions' => DutyService::getInstitutionsForUpserts($this->authorizer),
            'assignableUsers' => $this->assignableUsersForDutyForm(),
            'prefillInstitutionId' => $request->query('institution_id'),
            'assignableTenants' => GetTenantsForUpserts::execute('duties.create.padalinys', $this->authorizer),
            'assignableDuties' => DutyService::getAssignableExOfficioDuties($this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDutyRequest $request)
    {
        $duty = new Duty;

        $validatedData = $request->safe();
        $duty->fill(collect($validatedData)->except('types', 'roles', 'current_users', 'ex_officio_target_duty_ids', 'assignable_tenants')->toArray())->save();

        $duty->types()->sync($request->types);
        $duty->exOfficioTargetDuties()->sync($request->ex_officio_target_duty_ids ?? []);
        $duty->assignableTenants()->sync($this->buildAssignableTenantsSync($request->assignable_tenants ?? []));

        $this->handleUsersUpdate(new Collection($duty->current_users->pluck('id')), new Collection($request->current_users), $duty);

        // Load relationships needed for the response
        $duty->load('institution', 'types', 'current_users');

        // Return JSON response for AJAX requests (inline creation in wizard)
        if ($request->wantsJson() || $request->header('X-Inertia-Partial-Data')) {
            return response()->json([
                'success' => true,
                'message' => trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]),
                'duty' => $duty,
            ]);
        }

        return back()->with('success', trans_choice('messages.created', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Duty $duty)
    {
        $this->handleAuthorization('view', $duty);

        $duty->load('institution.tenant', 'users', 'activities.causer', 'types');

        return $this->inertiaResponse('Admin/People/ShowDuty', [
            'duty' => array_merge($duty->toArray(), [
                'sharepointPath' => $duty->institution?->tenant ? $duty->sharepoint_path() : null,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Duty $duty)
    {
        // Owning-tenant admins (can update the duty) and cross-tenant admins
        // (can only manage their tenant's reps) may both open this page.
        $this->handleAuthorization('managePeople', $duty);

        $user = request()->user();
        $canEditDuty = $user->can('update', $duty);

        $actingAssignableTenantIds = $canEditDuty
            ? collect()
            : $this->authorizer->forUser($user)->getTenants('duties.update.padalinys')->pluck('id');

        $baseRelations = ['institution', 'types', 'roles', 'exOfficioTargetDuties', 'assignableTenants'];

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
        $crossTenantRepsQuery = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', User::class)
            ->whereNotNull('tenant_id')
            ->active();

        if (! $canEditDuty) {
            $crossTenantRepsQuery->whereIn('tenant_id', $actingAssignableTenantIds->all());
        }

        $assignableTenantUsers = $crossTenantRepsQuery
            ->get(['dutiable_id', 'tenant_id'])
            ->groupBy('tenant_id')
            ->map(fn ($rows) => $rows->pluck('dutiable_id')->values()->all());

        return $this->inertiaResponse('Admin/People/EditDuty', [
            'duty' => $duty->toFullArray(),
            'canEditDuty' => $canEditDuty,
            'actingAssignableTenantIds' => $actingAssignableTenantIds->values()->all(),
            'assignableTenantUsers' => $assignableTenantUsers,
            'roles' => Role::all(),
            'dutyTypes' => GetAttachableTypesForDuty::execute()->values(),
            'assignableInstitutions' => DutyService::getInstitutionsForUpserts($this->authorizer),
            'assignableUsers' => $this->assignableUsersForDutyForm(),
            'assignableTenants' => GetTenantsForUpserts::execute('duties.update.padalinys', $this->authorizer),
            'assignableDuties' => $canEditDuty ? DutyService::getAssignableExOfficioDuties($this->authorizer, $duty) : collect(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Duty $duty)
    {
        $this->handleAuthorization('update', $duty);

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'current_users' => 'nullable|array',
            'institution_id' => 'required',
            'places_to_occupy' => 'required|numeric',
            'contacts_grouping' => 'required|in:none,study_program,tenant',
            'types' => 'nullable|array',
            'ex_officio_target_duty_ids' => 'nullable|array',
            'ex_officio_target_duty_ids.*' => ['ulid', 'distinct', 'exists:duties,id', 'not_in:'.$duty->id],
            'assignable_tenants' => 'nullable|array',
            'assignable_tenants.*.tenant_id' => 'required|integer|exists:tenants,id',
            'assignable_tenants.*.quota' => 'nullable|integer|min:1',
            'assignable_tenants.*.user_ids' => 'nullable|array',
            'assignable_tenants.*.user_ids.*' => 'string|exists:users,id',
        ]);

        $validator->after(function ($v) use ($request, $duty) {
            $authorizer = $this->authorizer->forUser($request->user());
            $hasGlobalDutyScope = $authorizer->check('duties.update.*');

            if (! $hasGlobalDutyScope) {
                $sourceTenantId = $duty->institution?->tenant_id;
                $targetIds = array_filter((array) $request->input('ex_officio_target_duty_ids', []));

                if ($targetIds && Duty::whereIn('id', $targetIds)
                    ->whereHas('institution', fn ($q) => $q->where('tenant_id', '!=', $sourceTenantId))
                    ->exists()) {
                    $v->errors()->add('ex_officio_target_duty_ids', __('Ex-officio pareigos turi priklausyti tam pačiam padaliniui.'));
                }

            }

            // Enforce per-tenant quota against the requested user_ids count.
            foreach ((array) $request->input('assignable_tenants', []) as $i => $row) {
                $quota = $row['quota'] ?? null;
                $userCount = count(array_unique((array) ($row['user_ids'] ?? [])));
                if ($quota !== null && $userCount > (int) $quota) {
                    $v->errors()->add("assignable_tenants.$i.user_ids", __('Padalinio kvota (:quota) viršyta.', ['quota' => $quota]));
                }
            }
        });

        $validator->validate();

        DB::transaction(function () use ($request, $duty) {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy', 'contacts_grouping'));

            // Only manage owning-tenant reps (tenant_id IS NULL) via the TransferList.
            $owningTenantCurrentIds = Dutiable::where('duty_id', $duty->id)
                ->where('dutiable_type', User::class)
                ->whereNull('tenant_id')
                ->active()
                ->pluck('dutiable_id');
            $this->handleUsersUpdate(
                new Collection($owningTenantCurrentIds),
                new Collection($request->current_users),
                $duty
            );

            $duty->institution()->disassociate();
            $duty->institution()->associate($request->institution_id);
            $duty->save();

            if ($request->has('roles')) {
                $roles = Role::find($request->roles);

                foreach ($roles as $role) {
                    if ($role->name == config('permission.super_admin_role_name')) {
                        abort(403, 'Negalima priskirti šios rolės pareigybėms! Bandykite iš naujo');
                    }
                }

                $duty->syncRoles($roles);
            } else {
                $duty->syncRoles([]);
            }

            $duty->types()->sync($request->types);

            // Sync ex-officio target duties and backfill derived Dutiable rows.
            $previousTargetIds = $duty->exOfficioTargetDuties()->pluck('duties.id')->all();
            $newTargetIds = array_filter($request->ex_officio_target_duty_ids ?? []);
            $duty->exOfficioTargetDuties()->sync($newTargetIds);

            $addedTargetIds = array_values(array_diff($newTargetIds, $previousTargetIds));
            $removedTargetIds = array_values(array_diff($previousTargetIds, $newTargetIds));

            if ($addedTargetIds || $removedTargetIds) {
                $dutyId = $duty->id;
                dispatch(function () use ($dutyId, $addedTargetIds, $removedTargetIds) {
                    $duty = Duty::find($dutyId);
                    if ($duty) {
                        BackfillExOfficioTargetDuty::execute($duty, $addedTargetIds, $removedTargetIds);
                    }
                })->afterCommit();
            }

            // Sync assignable tenants (with per-tenant quota and per-tenant reps).
            $previousTenantIds = $duty->assignableTenants()->pluck('tenants.id')->all();
            $assignableTenantsInput = $request->assignable_tenants ?? [];
            $newTenantIds = array_column($assignableTenantsInput, 'tenant_id');

            // End-date reps of tenants that are being removed entirely.
            $removedTenantIds = array_values(array_diff($previousTenantIds, $newTenantIds));
            foreach ($removedTenantIds as $tenantId) {
                DB::table('dutiables')
                    ->where('duty_id', $duty->id)
                    ->where('dutiable_type', User::class)
                    ->where('tenant_id', $tenantId)
                    ->whereNull('end_date')
                    ->update(['end_date' => now()->subDay()]);
            }

            $duty->assignableTenants()->sync($this->buildAssignableTenantsSync($assignableTenantsInput));

            // Sync per-tenant representative lists.
            foreach ($assignableTenantsInput as $row) {
                if (isset($row['tenant_id'])) {
                    $this->syncAssignableTenantUsers(
                        $duty,
                        (int) $row['tenant_id'],
                        array_values(array_unique((array) ($row['user_ids'] ?? [])))
                    );
                }
            }
        });

        return back()->with('success', trans_choice('messages.updated', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    /**
     * Return all users with an `is_recent` flag (active or active within the last 12 months).
     *
     * A user is considered recent when any of the following is true:
     * - has/had a dutiable row that is current or ended within the last 12 months
     * - account was created within the last 12 months
     * - `last_action` is within the last 12 months
     *
     * @return array<int, array{id: string, name: string, profile_photo_path: string|null, is_recent: bool}>
     */
    private function assignableUsersForDutyForm(): array
    {
        $cutoff = now()->subYear()->toDateTimeString();

        return User::query()
            ->select('users.id', 'users.name', 'users.profile_photo_path')
            ->selectRaw(
                '(users.created_at >= ? OR users.last_action >= ? OR EXISTS (
                    SELECT 1 FROM dutiables d
                    WHERE d.dutiable_id = users.id
                      AND d.dutiable_type = ?
                      AND (d.end_date IS NULL OR d.end_date >= ?)
                )) AS is_recent',
                [$cutoff, $cutoff, User::class, $cutoff]
            )
            ->orderBy('users.name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'profile_photo_path' => $u->profile_photo_path,
                'is_recent' => (bool) $u->is_recent,
            ])
            ->all();
    }

    /**
     * Build the sync array for assignableTenants from the request payload.
     *
     * @param  array<array{tenant_id: int, quota: int|null}>  $items
     * @return array<int, array{quota: int|null}>
     */
    private function buildAssignableTenantsSync(array $items): array
    {
        $sync = [];
        foreach ($items as $item) {
            $sync[$item['tenant_id']] = ['quota' => $item['quota'] ?? null];
        }

        return $sync;
    }

    /**
     * Count currently-active reps assigned for $tenantId on $duty and check against quota.
     * Returns true when the add is allowed, false when the quota would be exceeded.
     */
    private function tenantQuotaAllows(Duty $duty, int $tenantId, int $addCount = 1): bool
    {
        $quota = $duty->assignableTenants()->where('tenants.id', $tenantId)->first()?->pivot->quota;

        if ($quota === null) {
            return true;
        }

        $currentCount = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', User::class)
            ->where('tenant_id', $tenantId)
            ->active()
            ->count();

        return ($currentCount + $addCount) <= $quota;
    }

    private function handleUsersUpdate(Collection $existingUserIds, Collection $requestUserIds, Duty $duty)
    {
        $new = $requestUserIds->diff($existingUserIds);
        $removed = $existingUserIds->diff($requestUserIds);

        // Only touch owning-tenant rows (tenant_id IS NULL) — cross-tenant reps
        // are managed separately via the per-tenant user_ids in assignable_tenants.
        if ($removed->isNotEmpty()) {
            DB::table('dutiables')
                ->where('duty_id', $duty->id)
                ->whereIn('dutiable_id', $removed->all())
                ->where('dutiable_type', User::class)
                ->whereNull('tenant_id')
                ->whereNull('end_date')
                ->update(['end_date' => now()->subDay()]);
        }

        if ($new->isNotEmpty()) {
            $attachData = $new->mapWithKeys(fn ($userId) => [
                $userId => ['start_date' => now()->subDay(), 'tenant_id' => null],
            ])->all();
            $duty->users()->attach($attachData);
        }
    }

    /**
     * Sync cross-tenant representatives for one assignable tenant.
     * Diffs $requestedUserIds against the currently-active reps for that tenant
     * (identified by `dutiables.tenant_id = $tenantId`), end-dates removed reps,
     * and attaches new ones with the correct `tenant_id`.
     *
     * @param  array<string>  $requestedUserIds
     */
    private function syncAssignableTenantUsers(Duty $duty, int $tenantId, array $requestedUserIds): void
    {
        $today = now()->toDateString();

        $currentUserIds = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', User::class)
            ->where('tenant_id', $tenantId)
            ->active($today)
            ->pluck('dutiable_id')
            ->all();

        $toAdd = array_values(array_diff($requestedUserIds, $currentUserIds));
        $toRemove = array_values(array_diff($currentUserIds, $requestedUserIds));

        if ($toRemove) {
            DB::table('dutiables')
                ->where('duty_id', $duty->id)
                ->where('dutiable_type', User::class)
                ->where('tenant_id', $tenantId)
                ->whereIn('dutiable_id', $toRemove)
                ->whereNull('end_date')
                ->update(['end_date' => now()->subDay()]);
        }

        if ($toAdd) {
            $duty->users()->attach(
                collect($toAdd)->mapWithKeys(fn ($userId) => [
                    $userId => ['start_date' => now()->subDay(), 'tenant_id' => $tenantId],
                ])->all()
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Duty $duty)
    {
        $this->handleAuthorization('delete', $duty);

        $duty->delete();

        return redirect()->route('duties.index')->with('info', trans_choice('messages.deleted', 0, ['model' => trans_choice('entities.duty.model', 1)]));
    }

    public function restore(Duty $duty)
    {
        $this->handleAuthorization('restore', $duty);

        $duty->restore();

        return back()->with('success', 'Pareigybė sėkmingai atkurta!');
    }

    /**
     * Display the duty user update wizard page.
     */
    public function updateUsersWizard()
    {
        $this->handleAuthorization('viewAny', Duty::class);

        $currentUsersLoad = ['current_users' => function ($q) {
            $q->select('users.id', 'name', 'email', 'profile_photo_path')
                ->withPivot('start_date', 'end_date');
        }];

        // Get institutions the user can access, with their duties and current users
        // Include pivot data (start_date) to detect long-staying users
        $institutions = DutyService::getInstitutionsForUpserts($this->authorizer)
            ->load(['duties' => function ($query) use ($currentUsersLoad) {
                $query->with($currentUsersLoad);
            }, 'tenant:id,shortname']);

        // Also surface cross-tenant duties the user's tenant may assign reps to.
        // Each such institution is loaded with ONLY the assignable duty(ies),
        // carrying the `assignableTenants` pivot (quota) for the acting tenant.
        $adminReadTenantIds = $this->authorizer->forUser(request()->user())
            ->getTenants('duties.read.padalinys')->pluck('id');

        if ($adminReadTenantIds->isNotEmpty()) {
            // For cross-tenant duties the acting tenant only assigns *into*, show
            // only the reps explicitly assigned for the acting tenant (by tenant_id column).
            $tenantScopedCurrentUsers = ['current_users' => function ($q) use ($adminReadTenantIds) {
                $q->select('users.id', 'name', 'email', 'profile_photo_path')
                    ->wherePivotIn('tenant_id', $adminReadTenantIds->all())
                    ->withPivot('start_date', 'end_date', 'tenant_id');
            }];

            $crossTenantInstitutions = Institution::select('id', 'name', 'alias', 'tenant_id')
                ->whereHas('duties.assignableTenants', fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds))
                ->whereNotIn('id', $institutions->pluck('id'))
                ->with([
                    'tenant:id,shortname',
                    'duties' => function ($query) use ($tenantScopedCurrentUsers, $adminReadTenantIds) {
                        $query->whereHas('assignableTenants', fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds))
                            ->with($tenantScopedCurrentUsers)
                            ->with(['assignableTenants' => fn ($q) => $q->whereIn('tenants.id', $adminReadTenantIds)]);
                    },
                ])
                ->get();

            $crossTenantInstitutions->each(fn (Institution $i) => $i->setAttribute('is_external', true));

            $institutions = $institutions->concat($crossTenantInstitutions);
        }

        // Get data needed for creating institutions and duties
        $assignableTenants = GetTenantsForUpserts::execute('institutions.create.padalinys', $this->authorizer);
        $institutionTypes = Type::where('model_type', Institution::class)->get();

        return $this->inertiaResponse('Admin/People/DutyUserUpdateWizard', [
            // Immediate data for Step 1
            'institutions' => $institutions,
            // Data for inline institution creation (small datasets, load immediately)
            'assignableTenants' => $assignableTenants,
            'institutionTypes' => $institutionTypes,
            // Lazy loaded data - only fetched when explicitly requested via router.reload({ only: [...] })
            // Step 3: User assignment
            'users' => Inertia::optional(fn () => User::select('id', 'name', 'email', 'profile_photo_path')
                ->orderBy('name')->get()),
            'studyPrograms' => Inertia::optional(fn () => StudyProgram::select('id', 'name', 'degree', 'tenant_id')->get()),
            // Step 2: Duty creation (only needed if user wants to create a new duty)
            'dutyTypes' => Inertia::optional(fn () => GetAttachableTypesForDuty::execute()->values()),
        ]);
    }

    /**
     * Batch update users for a duty.
     */
    public function batchUpdateUsers(BatchUpdateDutyUsersRequest $request, Duty $duty)
    {
        $validated = $request->validated();

        $isOwningAdmin = $request->user()->can('update', $duty);

        // For cross-tenant admins, determine the acting tenant id.
        // The request may explicitly pass tenant_id; otherwise infer from the admin's
        // shared assignable tenants. Owning admins always write with tenant_id = null.
        $actingTenantId = null;
        if (! $isOwningAdmin) {
            $actingTenantId = $validated['tenant_id'] ?? null;

            if ($actingTenantId === null) {
                $duty->loadMissing('assignableTenants');
                $authorizer = $this->authorizer->forUser($request->user());
                $adminTenantIds = $authorizer->getTenants('duties.update.padalinys')->pluck('id');
                $actingTenantId = $adminTenantIds->intersect($duty->assignableTenants->pluck('id'))->first();
            }
        }

        $createdUsers = [];

        DB::transaction(function () use ($validated, $duty, $actingTenantId, &$createdUsers) {
            if (! empty($validated['new_users'])) {
                foreach ($validated['new_users'] as $newUserData) {
                    $user = User::create([
                        'name' => $newUserData['name'],
                        'email' => $newUserData['email'],
                        'phone' => $newUserData['phone'] ?? null,
                    ]);
                    $createdUsers[$newUserData['temp_id']] = $user;
                }
            }

            foreach ($validated['user_changes'] as $change) {
                $userId = $change['user_id'];

                if (str_starts_with($userId, 'new-')) {
                    if (isset($createdUsers[$userId])) {
                        $duty->users()->attach($createdUsers[$userId]->id, [
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                            'tenant_id' => $actingTenantId,
                        ]);
                    }

                    continue;
                }

                if ($change['action'] === 'add') {
                    // Look for an existing row scoped to the acting tenant.
                    $existingPivotQuery = $duty->dutiables()->where('dutiable_id', $userId);
                    if ($actingTenantId !== null) {
                        $existingPivotQuery->where('tenant_id', $actingTenantId);
                    } else {
                        $existingPivotQuery->whereNull('tenant_id');
                    }
                    $existingPivot = $existingPivotQuery->first();

                    if ($existingPivot) {
                        $existingPivot->update([
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                        ]);
                    } else {
                        $duty->users()->attach($userId, [
                            'start_date' => $change['start_date'] ?? now(),
                            'end_date' => $change['end_date'] ?? null,
                            'study_program_id' => $change['study_program_id'] ?? null,
                            'tenant_id' => $actingTenantId,
                        ]);
                    }
                } elseif ($change['action'] === 'remove') {
                    // End-date only the row belonging to the acting tenant.
                    $removeQuery = Dutiable::where('duty_id', $duty->id)
                        ->where('dutiable_type', User::class)
                        ->where('dutiable_id', $userId)
                        ->whereNull('end_date');

                    if ($actingTenantId !== null) {
                        $removeQuery->where('tenant_id', $actingTenantId);
                    } else {
                        $removeQuery->whereNull('tenant_id');
                    }

                    $removeQuery->update(['end_date' => $change['end_date'] ?? now()]);
                }
            }

            if (isset($validated['places_to_occupy'])) {
                $duty->update(['places_to_occupy' => $validated['places_to_occupy']]);
            }
        });

        return redirect()->route('duties.show', $duty)
            ->with('success', trans('Pareigybės nariai sėkmingai atnaujinti!'));
    }
}
