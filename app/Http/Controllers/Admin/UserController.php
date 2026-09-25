<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildUserIndexQuery;
use App\Actions\DeleteUserPassword;
use App\Actions\GenerateUserPassword;
use App\Actions\MergeUsers;
use App\Http\Controllers\AdminController;
use App\Http\Requests\GenerateUserPasswordRequest;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserRolesRequest;
use App\Http\Resources\TaskResource;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\Role;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ResourceServices\UserDutyService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexUserRequest $request)
    {
        $this->handleAuthorization('viewAny', User::class);

        $query = BuildUserIndexQuery::execute($request, $this->authorizer);

        $searchableColumns = ['name', 'email', 'phone'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenants',
                'permission' => 'users.read.padalinys',
                'handledFilters' => ['future_duty'],
            ]
        );

        $deletedCount = $this->getTrashedCount($query);

        // Trash view only: lets the table say why permanent deletion is refused.
        $query = $this->withForceDeleteBlockers($query, $request);

        $users = $query->paginate($request->getPerPage())
            ->withQueryString();

        $this->appendForceDeleteBlockedReason($users->getCollection(), $request);

        /** @var Collection<int, User> $collection */
        $collection = $users->getCollection();
        $collection->makeVisible(['last_action']);

        return $this->inertiaResponse('Admin/People/IndexUser', [
            'users' => [
                'data' => $collection->values(),
                'meta' => [
                    'total' => $users->total(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
            'showDeleted' => $request->getShowDeleted(),
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', User::class);

        return $this->inertiaResponse('Admin/People/CreateUser', [
            'roles' => Role::all(),
            'tenantsWithDuties' => UserDutyService::getTenantsWithDutiesForForm($this->authorizer),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer, 'users.create.padalinys'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request): void {
            $user = new User;

            $validatedData = $request->safe();
            $user->fill(collect($validatedData)->except(['current_duties', 'roles'])->toArray());

            $user->save();

            // Routed through the service so creation is held to the same tenant check
            // as editing; the old raw attach() accepted duty ids from any tenant.
            UserDutyService::syncDutiesForUser(
                new SupportCollection($request->validated('current_duties')),
                new SupportCollection,
                $user,
                $this->authorizer,
                'users.create.padalinys'
            );

            // only a super admin may assign roles
            if (User::find(Auth::id())->isSuperAdmin()) {
                $user->roles()->sync($request->validated('roles') ?? []);
            }
        });

        return $this->redirectResponse('users.index')->with('success', $this->entityMessage('created', 'user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->handleAuthorization('view', $user);

        $user->load([
            'current_duties.institution.tenant',
            'current_duties.current_users:id,name,profile_photo_path',
            'upcoming_duties.institution.tenant',
            'previous_duties.institution.tenant',
            'roles',
            'tasks.taskable',
            'tasks.users:id,name,email,profile_photo_path',
        ]);

        $user->append('has_password');

        $tasks = $user->tasks->sortByDesc('created_at')->values();
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => $tasks->whereNotNull('completed_at')->count(),
            'pending' => $tasks->whereNull('completed_at')->count(),
            'overdue' => $tasks->filter(fn ($t) => $t->isOverdue())->count(),
            'autoCompleting' => $tasks->filter(fn ($t) => ! $t->canBeManuallyCompleted())->count(),
        ];

        return $this->inertiaResponse('Admin/People/ShowUser', [
            'user' => $user->toFullArray(),
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'taskStats' => $taskStats,
            // Per-record, not from `auth.can`, which carries no flat permission names.
            'can' => [
                'update' => request()->user()->can('update', $user),
                'delete' => request()->user()->can('delete', $user),
                'updateRoles' => request()->user()->isSuperAdmin(),
                'managePasswords' => request()->user()->isSuperAdmin() && request()->user()->can('update', $user),
            ],
            // The Priskirti sheet's programme picker and the roles sheet's options: only someone
            // who may act on them needs them, and neither belongs on the first paint.
            'assignment' => Inertia::defer(fn () => request()->user()->can('update', $user) ? [
                'studyPrograms' => StudyProgram::query()->get(['id', 'name', 'degree', 'tenant_id']),
                'roles' => request()->user()->isSuperAdmin() ? Role::all(['id', 'name']) : [],
            ] : null, 'userPanels'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->handleAuthorization('update', $user);

        $actor = Auth::user();

        return $this->inertiaResponse('Admin/People/EditUser', [
            'user' => $user->load('current_duties')->makeVisible(['last_action'])->append('has_password')->toFullArray(),
            'canUpdateIdentity' => $actor->can('updateIdentity', $user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // TODO: make duty attach / detach work properly
        $this->handleAuthorization('update', $user);

        $actor = $request->user();
        $actorIsSuperAdmin = $actor->isSuperAdmin();
        $currentDutyIds = $user->current_duties->pluck('id');

        // UpdateUserRequest already rejects an identity change the actor may not make;
        // dropping the fields here as well means no future call path can slip one
        // through by skipping that validator.
        $fields = ['facebook_url', 'phone', 'profile_photo_path', 'profile_photo_focal_point', 'pronouns', 'show_pronouns'];

        if ($actor->can('updateName', $user)) {
            $fields[] = 'name';
        }

        if ($actor->can('updateIdentity', $user)) {
            $fields[] = 'email';
        }

        $mutation = function () use ($request, $user, $currentDutyIds, $actorIsSuperAdmin, $fields): void {
            // Duties and roles are managed on the record (Priskirti sheet, Rolės section), so the
            // form no longer posts them — and absent must mean "leave alone", never "remove all".
            if ($request->has('current_duties')) {
                UserDutyService::syncDutiesForUser(
                    new SupportCollection($request->current_duties ?? []),
                    $currentDutyIds,
                    $user,
                    $this->authorizer,
                    'users.update.padalinys'
                );
            }

            DB::transaction(function () use ($request, $user, $actorIsSuperAdmin, $fields): void {
                $user->update($request->safe()->only($fields));

                // only a super admin may change roles
                if ($actorIsSuperAdmin && $request->has('roles')) {
                    $user->roles()->sync($request->roles ?? []);
                }
            });
        };

        // Editing your own profile can drop the duties/roles that grant your
        // access. (A super admin can only self-lock by removing the Super Admin
        // role, but the analyzer detects that case too.)
        $couldAffectSelf = $user->is($actor);

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', $this->entityMessage('updated', 'user'));
    }

    /**
     * Replace a person's roles (super admin only). Roles are an association with a lifecycle of
     * their own, so they are edited on the record rather than in the form.
     */
    public function updateRoles(UpdateUserRolesRequest $request, User $user)
    {
        $actor = $request->user();

        $mutation = fn () => $user->roles()->sync($request->validated('roles') ?? []);

        // Removing your own Super Admin role locks you out, so it goes through the same guard as every access change.
        if ($warning = $this->guardSelfLockout($actor, $user->is($actor), $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', $this->entityMessage('updated', 'user'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->handleAuthorization('delete', $user);

        // UserPolicy blocks this too, but Gate::before grants super admins every
        // ability outright, so the policy never runs for them.
        abort_if($user->is(Auth::user()), 403, __('users.cannot_delete_self'));

        $user->delete();

        return $this->redirectResponse('users.index')->with('info', $this->entityMessage('deleted', 'user'));
    }

    /**
     * Merge two user accounts.
     */
    public function mergeUsers(MergeUsersRequest $request)
    {
        $keptUser = User::query()->find($request->kept_user_id);
        $sources = User::query()->whereIn('id', $request->validated('source_user_ids'))->get();

        MergeUsers::execute($keptUser, $sources);

        return back()->with('success', __('messages.user.merged'));
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user): RedirectResponse
    {
        return $this->restoreModel($user, $this->entityMessage('restored', 'user'));
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(User $user): RedirectResponse
    {
        $this->authorize('forceDelete', $user);

        abort_if($user->is(Auth::user()), 403, __('users.cannot_delete_self'));

        // The cleanup is handed to the trait rather than run here: it must not happen
        // until after the force-delete blockers have passed, or a refused delete
        // leaves the user stripped of every duty — and therefore of every tenant,
        // making them unreachable.
        //
        // Deleted row by row, never via duties()->detach(): a raw pivot delete fires
        // no model events, so DutiableChanged (permission cache reset) and the
        // ex-officio cascade both silently skip, orphaning rows derived from this
        // user's sources.
        return $this->forceDeleteModel(
            $user,
            $this->entityMessage('deleted', 'user'),
            function () use ($user): void {
                $user->dutiables()->get()->each->delete();
            },
        );
    }

    /**
     * Generate a random password for the user (super admin only).
     */
    public function generatePassword(User $user, GenerateUserPasswordRequest $request)
    {
        $password = GenerateUserPassword::execute($user);

        return back()->with('data', $password)
            ->with('success', __('messages.user.password_created'));
    }

    /**
     * Delete a user's password (super admin only).
     */
    public function deletePassword(User $user, GenerateUserPasswordRequest $request)
    {
        DeleteUserPassword::execute($user);

        return back()->with('success', __('messages.user.password_deleted'));
    }
}
