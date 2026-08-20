<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteUserPassword;
use App\Actions\GenerateUserPassword;
use App\Actions\MergeUsers;
use App\Http\Controllers\AdminController;
use App\Http\Requests\GenerateUserPasswordRequest;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ResourceServices\UserDutyService;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $query = User::query()->with([
            'duties:id,institution_id',
            'duties.institution:id,tenant_id',
            'duties.institution.tenant:id,shortname',
        ])->withCount('duties');

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
                // A user's tenants are derived from their duties, so someone with no
                // duties at all belongs to no tenant and would be invisible to every
                // tenant admin — including the one who just created them and now needs
                // to assign a duty (GitHub issue #249). Surface them to everyone;
                // UserPolicy applies the same carve-out, and refuses the ones holding
                // a directly assigned role.
                'permissionOrInclude' => fn ($query) => $query
                    ->orWhere(fn ($unclaimed) => $unclaimed
                        ->whereDoesntHave('duties')
                        ->whereDoesntHave('roles')),
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
            'previous_duties.institution.tenant',
            'roles',
            'tasks.taskable',
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

        $transformedTasks = $tasks->map(function (Task $task) {
            /** @var Model|null $taskable */
            $taskable = $task->taskable;

            return [
                'id' => $task->id,
                'name' => $task->name,
                'description' => $task->description,
                'due_date' => $task->due_date?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
                'created_at' => $task->created_at->toISOString(),
                'action_type' => $task->action_type?->value,
                'metadata' => $task->metadata,
                'progress' => $task->getProgress(),
                'is_overdue' => $task->isOverdue(),
                'can_be_manually_completed' => $task->canBeManuallyCompleted(),
                'icon' => $task->icon,
                'color' => $task->color,
                'taskable' => $taskable ? [
                    'id' => $taskable->getKey(),
                    'name' => $taskable->getAttribute('title') ?? $taskable->getAttribute('name') ?? null,
                    'type' => $task->taskable_type,
                ] : null,
                'taskable_type' => $task->taskable_type ?? '',
                'taskable_id' => $task->taskable_id,
                'users' => $task->users->map(fn (User $u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'profile_photo_path' => $u->profile_photo_path,
                ])->all(),
            ];
        });

        return $this->inertiaResponse('Admin/People/ShowUser', [
            'user' => $user->toFullArray(),
            'tasks' => $transformedTasks,
            'taskStats' => $taskStats,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $this->handleAuthorization('update', $user);

        // Institution/tenant loaded so the duty tables and transfer-list target
        // labels can attribute a duty rather than showing a bare, unattributable
        // name (the same name commonly repeats across institutions).
        $user->load('current_duties.institution.tenant', 'previous_duties.institution.tenant', 'roles');

        $actor = Auth::user();

        return $this->inertiaResponse('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action'])->append('has_password')->toFullArray(),
            'roles' => Role::all(...),
            'tenantsWithDuties' => fn () => UserDutyService::getTenantsWithDutiesForForm($this->authorizer, 'users.update.all'),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer, 'users.update.padalinys'),
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

        if ($actor->can('updateIdentity', $user)) {
            $fields = array_merge(['name', 'email'], $fields);
        }

        $mutation = function () use ($request, $user, $currentDutyIds, $actorIsSuperAdmin, $fields): void {
            UserDutyService::syncDutiesForUser(
                new SupportCollection($request->current_duties ?? []),
                $currentDutyIds,
                $user,
                $this->authorizer,
                'users.update.padalinys'
            );

            DB::transaction(function () use ($request, $user, $actorIsSuperAdmin, $fields): void {
                $user->update($request->safe()->only($fields));

                // only a super admin may change roles
                if ($actorIsSuperAdmin) {
                    $user->roles()->sync($request->has('roles') ? $request->roles : []);
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
     * Show the merge users form.
     */
    public function merge()
    {
        $this->handleAuthorization('merge', User::class);

        $users = User::query()
            ->with([
                'duties:id,institution_id',
                'duties.institution:id,tenant_id',
                'duties.institution.tenant:id,shortname',
            ])
            ->withCount('duties')
            ->get();

        return $this->inertiaResponse('Admin/People/MergeUser', [
            'users' => $users,
        ]);
    }

    /**
     * Merge two user accounts.
     */
    public function mergeUsers(MergeUsersRequest $request)
    {
        $keptUser = User::query()->find($request->kept_user_id);
        $mergedUser = User::query()->find($request->merged_user_id);

        MergeUsers::execute($keptUser, $mergedUser);

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

        // The detach is handed to the trait rather than run here: it must not happen
        // until after the force-delete blockers have passed, or a refused delete
        // leaves the user stripped of every duty — and therefore of every tenant,
        // making them unreachable.
        return $this->forceDeleteModel(
            $user,
            $this->entityMessage('deleted', 'user'),
            function () use ($user): void {
                $user->duties()->detach();
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
