<?php

namespace App\Http\Controllers\Admin;

use App\Actions\BuildUserIndexQuery;
use App\Actions\DeleteUserPassword;
use App\Actions\FindMergeableUserDuplicates;
use App\Actions\ForgetUserAccess;
use App\Actions\GenerateUserPassword;
use App\Actions\Media\SyncImageMedia;
use App\Actions\MergeUsers;
use App\Http\Controllers\AdminController;
use App\Http\Requests\GenerateUserPasswordRequest;
use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\MergeUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UpdateUserSuperAdminRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserRowResource;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\PostCreateRedirect;
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

        $buildQuery = fn (bool $includeDuplicates = true) => $this->applyTanstackFilters(
            BuildUserIndexQuery::execute($request, $this->authorizer, $includeDuplicates),
            $request,
            $this->tableService,
            ['name', 'email', 'phone'],
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenants',
                'permission' => 'users.read.padalinys',
                'handledFilters' => ['future_duty', 'membership', 'duplicates'],
            ]
        );

        // The duplicate filter only applies to live accounts, so it must not hide the trash door.
        $deletedCount = $this->getTrashedCount($buildQuery(includeDuplicates: false));
        $query = $buildQuery();

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
                'data' => $collection->map(fn (User $user): array => UserRowResource::make($user)->resolve($request))->values(),
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
            'tenantsWithDuties' => UserDutyService::getTenantsWithDutiesForForm($this->authorizer),
            'permissableTenants' => UserDutyService::getPermissableTenants($this->authorizer, 'users.create.padalinys'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, SyncImageMedia $syncImage, PostCreateRedirect $redirect)
    {
        $user = DB::transaction(function () use ($request, $syncImage): User {
            $user = new User;

            $validatedData = $request->safe();
            $user->fill(collect($validatedData)->except(['current_duties', 'profile_photo_media'])->toArray());

            $user->save();

            $syncImage->fromValidated($user, 'profile_photo', $request->validated(), 'profile_photo_media', $request->user());

            // Routed through the service so creation is held to the same tenant check
            // as editing; the old raw attach() accepted duty ids from any tenant.
            UserDutyService::syncDutiesForUser(
                new SupportCollection($request->validated('current_duties')),
                new SupportCollection,
                $user,
                $this->authorizer,
                'users.create.padalinys'
            );

            return $user;
        });

        return redirect($redirect->url($request->user(), $user, 'users.show', 'view', 'users.index'))
            ->with('success', $this->entityMessage('created', 'user'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->handleAuthorization('view', $user);

        $loadUser = fn () => once(function () use ($user) {
            $user->loadMissing([
                'current_duties.institution.tenant',
                'current_duties.current_users:id,name,profile_photo_path',
                'upcoming_duties.institution.tenant',
                'previous_duties.institution.tenant',
                'previous_duties.formerNames',
                'tasks.taskable',
                'tasks.users:id,name,email,profile_photo_path',
                'tasks.tenants',
            ]);
            $user->append('has_password');

            return $user;
        });

        $tasks = fn () => once(fn () => $loadUser()->tasks->sortByDesc('created_at')->values());

        $taskStats = fn () => once(function () use ($tasks) {
            $t = $tasks();

            return [
                'total' => $t->count(),
                'completed' => $t->whereNotNull('completed_at')->count(),
                'pending' => $t->whereNull('completed_at')->count(),
                'overdue' => $t->filter(fn ($item) => $item->isOverdue())->count(),
                'autoCompleting' => $t->filter(fn ($item) => ! $item->canBeManuallyCompleted())->count(),
            ];
        });

        $actor = fn () => once(fn () => request()->user());

        return $this->inertiaResponse('Admin/People/ShowUser', [
            'user' => fn () => $loadUser()->toFullArray(),
            'isSuperAdmin' => fn () => $user->isSuperAdmin(),
            'tasks' => fn () => TaskResource::collection($tasks())->resolve(),
            'taskStats' => fn () => $taskStats(),
            // Per-record, not from `auth.can`, which carries no flat permission names.
            'can' => fn () => [
                'update' => (bool) $actor()->can('update', $user),
                'delete' => (bool) $actor()->can('delete', $user),
                'updateSuperAdmin' => (bool) $actor()->isSuperAdmin(),
                'managePasswords' => (bool) ($actor()->isSuperAdmin() && $actor()->can('update', $user)),
            ],
            // The Priskirti sheet's programme picker: only someone who may act on it needs it, and it
            // does not belong on the first paint.
            'assignment' => Inertia::defer(fn () => $actor()->can('update', $user) ? [
                'studyPrograms' => StudyProgram::query()->get(['id', 'name', 'degree', 'tenant_id']),
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
            'user' => [
                ...$user->load('current_duties')->makeVisible(['last_action'])->append('has_password')->toFullArray(),
                'profile_photo_media' => $user->imageData('profile_photo'),
            ],
            'canUpdateIdentity' => $actor->can('updateIdentity', $user),
            'duplicates' => Inertia::defer(fn () => app(FindMergeableUserDuplicates::class)->execute($actor, $user)),
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
        $currentDutyIds = $user->current_duties->pluck('id');

        // UpdateUserRequest already rejects an identity change the actor may not make;
        // dropping the fields here as well means no future call path can slip one
        // through by skipping that validator.
        $fields = ['facebook_url', 'phone', 'pronouns', 'show_pronouns'];

        if ($actor->can('updateName', $user)) {
            $fields[] = 'name';
        }

        if ($actor->can('updateIdentity', $user)) {
            $fields[] = 'email';
        }

        $mutation = function () use ($request, $user, $currentDutyIds, $fields): void {
            // Duties are managed on the record (Priskirti sheet), so the form no longer posts
            // them — and absent must mean "leave alone", never "remove all".
            if ($request->has('current_duties')) {
                UserDutyService::syncDutiesForUser(
                    new SupportCollection($request->current_duties ?? []),
                    $currentDutyIds,
                    $user,
                    $this->authorizer,
                    'users.update.padalinys'
                );
            }

            DB::transaction(function () use ($request, $user, $fields): void {
                $user->update($request->safe()->only($fields));
                app(SyncImageMedia::class)->fromValidated($user, 'profile_photo', $request->validated(), 'profile_photo_media', $request->user());
            });
        };

        // Editing your own profile can drop the duties that grant your access.
        $couldAffectSelf = $user->is($actor);

        if ($warning = $this->guardSelfLockout($actor, $couldAffectSelf, $request, $mutation)) {
            return $warning;
        }

        return back()->with('success', $this->entityMessage('updated', 'user'));
    }

    /**
     * Make a person a super admin or take it away. Other roles reach people only through their
     * duties' responsibilities, which scope them to a padalinys; a role held by the person
     * directly would apply in every padalinys they hold any duty in.
     */
    public function updateSuperAdmin(UpdateUserSuperAdminRequest $request, User $user)
    {
        $actor = $request->user();
        $superAdminRole = config('permission.super_admin_role_name');

        $mutation = fn () => $request->boolean('is_super_admin')
            ? $user->assignRole($superAdminRole)
            : $user->removeRole($superAdminRole);

        // Removing your own Super Admin role locks you out, so it goes through the same guard as every access change.
        if ($warning = $this->guardSelfLockout($actor, $user->is($actor), $request, $mutation)) {
            return $warning;
        }

        ForgetUserAccess::execute([$user->getKey()]);

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
     * Merge duplicate user accounts into one.
     */
    public function mergeUsers(MergeUsersRequest $request): RedirectResponse
    {
        $keptUser = $request->keptUser();

        MergeUsers::execute($keptUser, $request->sourceUsers(), $request->validated('login_email'), $request->user(), $request->validated('dutiable_email_ids', []));

        // Merging from a duplicate's own edit page would send "back" to a deleted record.
        $redirect = $request->validated('redirect_to_kept')
            ? to_route('users.edit', $keptUser)
            : back();

        return $redirect->with('success', __('messages.user.merged'));
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
