<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BackfillExOfficioTargetDuty;
use App\Actions\GetAttachableTypesForDuty;
use App\Actions\GetTenantsForUpserts;
use App\Actions\MergeDuties;
use App\Http\Controllers\AdminController;
use App\Http\Requests\BatchUpdateDutyUsersRequest;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Requests\MergeDutiesRequest;
use App\Http\Requests\StoreDutyRequest;
use App\Http\Requests\UpdateDutyRequest;
use App\Http\Traits\HandlesSoftDeletes;
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
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DutyController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

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
            // Feeds Duty::forceDeleteBlockedReason() without a query per row.
        ])->withCount('dutiables');

        $searchableColumns = ['name', 'email'];

        // Search / sort / column filters / soft-delete only — tenant scoping is
        // applied below so the assignable-tenants alternative is ORed with the
        // read scope inside one group (not appended after the search filters).
        $query = $this->applyTanstackFilters($query, $request, $this->tableService, $searchableColumns);

        $this->applyDataQualityFilter($query, $request->getFilters()['data_quality'] ?? null);

        $authorizer = $this->authorizer->forUser($request->user());
        $hasGlobalReadScope = $authorizer->check('duties.read.*') || $request->user()?->isSuperAdmin();

        if (! $hasGlobalReadScope) {
            $adminTenantIds = $authorizer->getTenants('duties.read.padalinys')->pluck('id')->all();
            // Cross-tenant duties (the user's tenant is in assignableTenants) are
            // included by default; the `show_external` table filter hides them.
            $includeExternal = ($request->getFilters()['show_external'] ?? true) !== false;

            $query->where(function ($q) use ($adminTenantIds, $includeExternal): void {
                $q->whereHas('institution.tenant', fn ($t) => $t->whereIn('id', $adminTenantIds));

                if ($includeExternal) {
                    $q->orWhereHas('assignableTenants', fn ($a) => $a->whereIn('tenants.id', $adminTenantIds));
                }
            });
        }

        $deletedCount = $this->getTrashedCount($query);

        $duties = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/People/IndexDuty', [
            'duties' => [
                'data' => $duties->getCollection()->map(function ($duty) {
                    /** @var Duty $duty */
                    return $duty->append('force_delete_blocked_reason')->toFullArray();
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
            'showDeleted' => $request->boolean('showDeleted', false),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Narrow the index to a single data-quality slice. Surfaces the cheapest
     * cleanup levers the duties table offers: duties nobody currently holds,
     * duties missing a localized name (so they render blank in that locale), and
     * duties where one person holds two concurrently-active rows — the residual
     * cross-tenant pairs the de-duplication migration left for human review.
     */
    private function applyDataQualityFilter($query, ?string $dataQuality): void
    {
        match ($dataQuality) {
            'vacant' => $query->whereDoesntHave('current_users'),
            'missing_en_name' => $query->whereRaw($this->localeMissingClause('en')),
            'missing_lt_name' => $query->whereRaw($this->localeMissingClause('lt')),
            'duplicate_holders' => $query->whereExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('dutiables as dup')
                    ->whereColumn('dup.duty_id', 'duties.id')
                    ->where('dup.dutiable_type', User::class)
                    ->where(function ($q): void {
                        $q->whereNull('dup.end_date')->orWhere('dup.end_date', '>=', now());
                    })
                    ->groupBy('dup.dutiable_id')
                    ->havingRaw('COUNT(*) > 1');
            }),
            default => null,
        };
    }

    /**
     * Raw SQL matching rows whose translatable `name` lacks a non-empty value
     * for $locale. Spatie stores the field as JSON; the extractor differs by
     * driver. "Blank" covers three storage shapes — key absent, an explicit
     * JSON null (`{"lt":null}`, which JSON_UNQUOTE turns into the literal
     * string "null" on MySQL), and an empty string.
     */
    private function localeMissingClause(string $locale): string
    {
        $path = "$.{$locale}";

        return DB::getDriverName() === 'sqlite'
            // SQLite's json_extract already collapses a JSON null to SQL NULL,
            // so key-absent and explicit-null are both caught by IS NULL.
            ? "(json_extract(name, '{$path}') IS NULL OR json_extract(name, '{$path}') = '')"
            : "(JSON_EXTRACT(name, '{$path}') IS NULL OR JSON_TYPE(JSON_EXTRACT(name, '{$path}')) = 'NULL' OR JSON_UNQUOTE(JSON_EXTRACT(name, '{$path}')) = '')";
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

        $duty->load('institution.tenant', 'users', 'types');

        // Sibling duties for the sidebar (queried separately to keep the payload lean).
        $otherDuties = $duty->institution
            ? $duty->institution->duties()
                ->where('id', '!=', $duty->id)
                ->orderBy('order')
                ->with('current_users:id,name,profile_photo_path')
                ->get(['id', 'name', 'institution_id', 'places_to_occupy', 'order'])
                ->map(fn (Duty $sibling) => $sibling->toArray())
                ->values()
            : collect();

        // Next / last meeting (HasManyDeep through the institution).
        $nextMeeting = $duty->meetings()
            ->where('start_time', '>=', now())
            ->orderBy('start_time')
            ->first(['meetings.id', 'meetings.title', 'meetings.start_time']);

        $lastMeeting = $duty->meetings()
            ->where('start_time', '<', now())
            ->orderByDesc('start_time')
            ->first(['meetings.id', 'meetings.title', 'meetings.start_time']);

        return $this->inertiaResponse('Admin/People/ShowDuty', [
            'duty' => array_merge($duty->toArray(), [
                'sharepointPath' => $duty->institution?->tenant ? $duty->sharepoint_path() : null,
                'other_duties' => $otherDuties,
                'next_meeting' => $nextMeeting?->toArray(),
                'last_meeting' => $lastMeeting?->toArray(),
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
        // Must match Duty::current_users() semantics: end_date >= now() (datetime) so
        // a rep whose end_date is today is already considered inactive.
        // Ex-officio rows are left out — they are not the picker's to grant or revoke.
        $crossTenantRepsQuery = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', User::class)
            ->whereNotNull('tenant_id')
            ->whereNull('via_dutiable_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });

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
    public function update(UpdateDutyRequest $request, Duty $duty)
    {
        $this->handleAuthorization('update', $duty);

        $actor = $request->user();

        $mutation = fn () => DB::transaction(function () use ($request, $duty): void {
            $duty->update($request->only('name', 'description', 'email', 'places_to_occupy', 'contacts_grouping'));

            // Only manage owning-tenant reps (tenant_id IS NULL) via the TransferList.
            // Ex-officio rows are excluded to match DutyForm.vue, which filters them out
            // of `current_users` before posting: counting them here made every save of a
            // target duty read them as "removed" and end-date the whole ex-officio cohort.
            $owningTenantCurrentIds = Dutiable::where('duty_id', $duty->id)
                ->where('dutiable_type', User::class)
                ->whereNull('tenant_id')
                ->whereNull('via_dutiable_id')
                ->where(function ($query): void {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
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
                dispatch(function () use ($dutyId, $addedTargetIds, $removedTargetIds): void {
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
                $this->endDateDutiables(
                    Dutiable::where('duty_id', $duty->id)
                        ->where('dutiable_type', User::class)
                        ->where('tenant_id', $tenantId)
                        ->where(function ($query): void {
                            $query->whereNull('end_date')
                                ->orWhere('end_date', '>=', now());
                        }),
                    now()->subDay()
                );
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

        // The acting user holding this duty may lose access if its roles, types
        // or institution change beneath them.
        $couldAffectSelf = ! $actor->isSuperAdmin()
            && Dutiable::where('duty_id', $duty->id)
                ->where('dutiable_type', User::class)
                ->where('dutiable_id', $actor->id)
                ->where(function ($query): void {
                    $query->whereNull('end_date')->orWhere('end_date', '>=', now());
                })
                ->exists();

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

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
                'is_recent' => (bool) $u->getAttribute('is_recent'),
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
     * End-date the matched dutiable rows through the model layer.
     *
     * A mass `update()` — on `DB::table()` or on an Eloquent builder — fires no
     * model events, so `DutiableChanged` never reaches SyncExOfficioDutiables and
     * the rows derived from an ended membership keep their open period forever.
     * Saving row by row costs one write each and is always a handful of rows.
     *
     * A plain loop rather than `each()`: that helper treats a falsy return as
     * "stop", so one refused save would silently skip every remaining row.
     *
     * @param  EloquentBuilder<Dutiable>  $query
     */
    private function endDateDutiables(EloquentBuilder $query, mixed $endDate): void
    {
        foreach ($query->get() as $dutiable) {
            $dutiable->update(['end_date' => $endDate]);
        }
    }

    private function handleUsersUpdate(Collection $existingUserIds, Collection $requestUserIds, Duty $duty)
    {
        $new = $requestUserIds->diff($existingUserIds);
        $removed = $existingUserIds->diff($requestUserIds);

        // Only touch owning-tenant rows (tenant_id IS NULL) — cross-tenant reps
        // are managed separately via the per-tenant user_ids in assignable_tenants.
        // Ex-officio rows are off-limits too: they end when their source does.
        if ($removed->isNotEmpty()) {
            $this->endDateDutiables(
                Dutiable::where('duty_id', $duty->id)
                    ->whereIn('dutiable_id', $removed->all())
                    ->where('dutiable_type', User::class)
                    ->whereNull('tenant_id')
                    ->whereNull('via_dutiable_id')
                    ->where(function ($query): void {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    }),
                now()->subDay()
            );
        }

        if ($new->isNotEmpty()) {
            $attachData = $new->mapWithKeys(fn ($userId) => [
                $userId => ['start_date' => now()->subDay(), 'tenant_id' => null],
            ])->all();
            $duty->attachAudited('users', $attachData);
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

        // Ex-officio rows are excluded on both sides: DutyController@edit keeps them
        // out of the tenant's picker, so they must not read as "removed" here either.
        $currentUserIds = Dutiable::where('duty_id', $duty->id)
            ->where('dutiable_type', User::class)
            ->where('tenant_id', $tenantId)
            ->whereNull('via_dutiable_id')
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->pluck('dutiable_id')
            ->all();

        $toAdd = array_values(array_diff($requestedUserIds, $currentUserIds));
        $toRemove = array_values(array_diff($currentUserIds, $requestedUserIds));

        if ($toRemove) {
            $this->endDateDutiables(
                Dutiable::where('duty_id', $duty->id)
                    ->where('dutiable_type', User::class)
                    ->where('tenant_id', $tenantId)
                    ->whereNull('via_dutiable_id')
                    ->whereIn('dutiable_id', $toRemove)
                    ->where(function ($query): void {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    }),
                now()->subDay()
            );
        }

        if ($toAdd) {
            $duty->attachAudited(
                'users',
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

    public function restore(Duty $duty): RedirectResponse
    {
        return $this->restoreModel($duty);
    }

    /**
     * Show the form for merging duplicate duties into one.
     *
     * `target_duty_id` pre-selects the kept duty (arriving from the duties index
     * row action or the duplicate-duty warning). The full duty list is sent, as
     * MergeStudyPrograms does for study programs — institution scoping (a
     * cross-institution merge is almost never intentional) happens client-side
     * against `institution_id`, alongside the target selection.
     */
    public function merge(Request $request)
    {
        $this->handleAuthorization('viewAny', Duty::class);

        $duties = Duty::query()
            ->with(['institution:id,name,tenant_id', 'institution.tenant:id,shortname'])
            ->withCount('dutiables')
            ->orderBy('name')
            ->get();

        return $this->inertiaResponse('Admin/People/MergeDuty', [
            'duties' => $duties->map->toFullArray(),
            'targetDutyId' => $request->string('target_duty_id')->toString() ?: null,
        ]);
    }

    /**
     * Merge one or more duplicate duties into a single kept duty.
     */
    public function mergeDuties(MergeDutiesRequest $request)
    {
        $kept = Duty::findOrFail($request->validated('target_duty_id'));
        $sources = Duty::query()->whereIn('id', $request->validated('source_duty_ids'))->get();

        $summary = MergeDuties::execute($kept, $sources);

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
    public function updateUsersWizard()
    {
        $this->handleAuthorization('viewAny', Duty::class);

        $currentUsersLoad = ['current_users' => function ($q): void {
            $q->select('users.id', 'name', 'email', 'profile_photo_path')
                ->withPivot('start_date', 'end_date');
        }];

        // Get institutions the user can access, with their duties and current users
        // Include pivot data (start_date) to detect long-staying users
        $institutions = DutyService::getInstitutionsForUpserts($this->authorizer)
            ->load(['duties' => function ($query) use ($currentUsersLoad): void {
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

        $mutation = function () use ($validated, $duty, $actingTenantId, &$createdUsers): void {
            DB::transaction(function () use ($validated, $duty, $actingTenantId, &$createdUsers): void {
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
                            $duty->attachAudited('users', $createdUsers[$userId]->id, [
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
                            $duty->attachAudited('users', $userId, [
                                'start_date' => $change['start_date'] ?? now(),
                                'end_date' => $change['end_date'] ?? null,
                                'study_program_id' => $change['study_program_id'] ?? null,
                                'tenant_id' => $actingTenantId,
                            ]);
                        }
                    } elseif ($change['action'] === 'remove') {
                        // End-date only the active row belonging to the acting tenant.
                        // Ex-officio rows follow their source and are never removed here.
                        $removeQuery = Dutiable::where('duty_id', $duty->id)
                            ->where('dutiable_type', User::class)
                            ->where('dutiable_id', $userId)
                            ->whereNull('via_dutiable_id')
                            ->where(function ($query): void {
                                $query->whereNull('end_date')
                                    ->orWhere('end_date', '>=', now());
                            });

                        if ($actingTenantId !== null) {
                            $removeQuery->where('tenant_id', $actingTenantId);
                        } else {
                            $removeQuery->whereNull('tenant_id');
                        }

                        $this->endDateDutiables($removeQuery, $change['end_date'] ?? now());
                    }
                }

                if (isset($validated['places_to_occupy'])) {
                    $duty->update(['places_to_occupy' => $validated['places_to_occupy']]);
                }
            });
        };

        // Removing themselves from this duty may strip the acting user's own access.
        $actor = $request->user();
        $couldAffectSelf = ! $actor->isSuperAdmin()
            && collect($validated['user_changes'])
                ->contains(fn ($change) => (string) $change['user_id'] === (string) $actor->id);

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return redirect()->route('duties.show', $duty)
            ->with('success', trans('Pareigybės nariai sėkmingai atnaujinti!'));
    }

    public function forceDelete(Duty $duty): RedirectResponse
    {
        return $this->forceDeleteModel($duty);
    }
}
