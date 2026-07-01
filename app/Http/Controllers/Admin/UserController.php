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
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserController extends AdminController
{
    use HasTanstackTables;

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
            ]
        );

        $users = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

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
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        DB::transaction(function () use ($request) {
            $user = new User;

            $validatedData = $request->safe();
            $user->fill(collect($validatedData)->except(['current_duties', 'roles'])->toArray());

            $user->save();

            foreach ($request->current_duties as $duty) {
                $user->duties()->attach($duty, ['start_date' => now()->subDay()]);
            }

            // check if user is super admin
            if (User::find(Auth::id())->isSuperAdmin()) {
                if ($request->has('roles')) {
                    $user->roles()->sync($request->roles);
                } else {
                    $user->syncRoles([]);
                }
            }
        });

        return $this->redirectResponse('users.index')->with('success', 'Kontaktas sėkmingai sukurtas!');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->handleAuthorization('view', $user);

        $user->load([
            'current_duties.institution.tenant',
            'previous_duties.institution.tenant',
            'roles',
            'tasks.taskable',
        ]);

        $user->makeVisible(['last_action'])->append('has_password');

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
                    'type' => class_basename($task->taskable_type),
                ] : null,
                'taskable_type' => class_basename($task->taskable_type ?? ''),
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

        $user->load('current_duties', 'previous_duties', 'roles');

        return $this->inertiaResponse('Admin/People/EditUser', [
            'user' => $user->makeVisible(['last_action'])->append('has_password')->toFullArray(),
            'roles' => fn () => Role::all(),
            'tenantsWithDuties' => fn () => UserDutyService::getTenantsWithDutiesForForm($this->authorizer),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer),
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

        $mutation = function () use ($request, $user, $currentDutyIds, $actorIsSuperAdmin) {
            UserDutyService::syncDutiesForUser(
                new SupportCollection($request->current_duties ?? []),
                $currentDutyIds,
                $user,
                $this->authorizer
            );

            DB::transaction(function () use ($request, $user, $actorIsSuperAdmin) {
                $user->update($request->only('name', 'email', 'facebook_url', 'phone', 'profile_photo_path', 'profile_photo_focal_point', 'pronouns', 'show_pronouns'));

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

        return back()->with('success', 'Kontaktas sėkmingai atnaujintas!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->handleAuthorization('delete', $user);

        $user->delete();

        return $this->redirectResponse('users.index')->with('info', 'Kontaktas sėkmingai ištrintas!');
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

        return back()->with('success', 'Kontaktai sėkmingai sujungti!');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(User $user, Request $request)
    {
        $this->handleAuthorization('restore', $user);

        $user->restore();

        return back()->with('success', 'Kontaktas sėkmingai atkurtas!');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete($id, Request $request)
    {
        $user = User::withTrashed()->findOrFail($id);

        $this->handleAuthorization('forceDelete', $user);

        $user->duties()->detach();
        $user->forceDelete();

        return $this->redirectResponse('users.index')->with('success', 'Kontaktas sėkmingai ištrintas!');
    }

    /**
     * Generate a random password for the user (super admin only).
     */
    public function generatePassword(User $user, GenerateUserPasswordRequest $request)
    {
        $password = GenerateUserPassword::execute($user);

        return back()->with('data', $password)
            ->with('success', 'Slaptažodis sėkmingai sukurtas!');
    }

    /**
     * Delete a user's password (super admin only).
     */
    public function deletePassword(User $user, GenerateUserPasswordRequest $request)
    {
        DeleteUserPassword::execute($user);

        return back()->with('success', 'Slaptažodis sėkmingai ištrintas!');
    }
}
