<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Requests\IndexRoleRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\SyncRoleAttachableTypesRequest;
use App\Http\Requests\SyncRoleDutiesRequest;
use App\Http\Requests\SyncRolePermissionGroupRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use App\Services\TanstackTableService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Response;

class RoleController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRoleRequest $request): Response
    {
        $this->handleAuthorization('viewAny', Role::class);

        $query = Role::query();

        $searchableColumns = ['name'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $roles = $query->paginate($request->getPerPage())
            ->withQueryString();

        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/Permissions/IndexRole', [
            'roles' => [
                'data' => $roles->items(),
                'meta' => [
                    'total' => $roles->total(),
                    'per_page' => $roles->perPage(),
                    'current_page' => $roles->currentPage(),
                    'last_page' => $roles->lastPage(),
                    'from' => $roles->firstItem(),
                    'to' => $roles->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $sorting,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Role::class);

        return $this->inertiaResponse('Admin/Permissions/CreateRole');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $this->handleAuthorization('create', Role::class);

        $role = Role::create($request->validated());

        return redirect()->route('roles.index')->with('success', $this->entityMessage('created', 'role'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $this->handleAuthorization('view', $role);

        $role->load('permissions:id,name');

        // show role
        return $this->inertiaResponse('Admin/Permissions/ShowRole', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $this->handleAuthorization('update', $role);

        // not load Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', __('messages.role.not_editable'));
        }

        $role->load('permissions:id,name', 'duties:id,name');

        $tenantsWithDuties = Tenant::orderBy('shortname')->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->when(! auth()->user()?->isSuperAdmin(), function ($query): void {
                $query->whereIn('id', User::find(Auth::id())->tenants->pluck('id'));
            })->get();

        // Get all available permissions grouped by model type
        $allAvailablePermissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);

            return $parts[0]; // Model type (e.g., 'tags', 'news')
        });

        // edit role
        return $this->inertiaResponse('Admin/Permissions/EditRole', [
            'role' => [
                ...$role->toArray(),
                'attachable_types' => $role->attachable_types->pluck('id')->toArray(),
            ],
            'tenantsWithDuties' => $tenantsWithDuties,
            'allTypes' => Type::all(),
            'allAvailablePermissions' => $allAvailablePermissions->map(fn ($permissions) => $permissions->pluck('name')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $this->handleAuthorization('update', $role);

        // not update Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', __('messages.role.not_editable'));
        }

        $role->update($request->validated());

        return back()->with('success', $this->entityMessage('updated', 'role'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->handleAuthorization('delete', $role);

        // check if role is not Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', __('messages.role.not_deletable'));
        }

        $this->clearCacheforRoleUsers($role);

        $role->delete();

        return back()->with('success', $this->entityMessage('deleted', 'role'));
    }

    public function syncPermissionGroup(Role $role, string $model, SyncRolePermissionGroupRequest $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validated();

        $newPermissions = [];

        foreach (array_filter($validated) as $ability => $scope) {
            $newPermissions[] = $model.'.'.$ability.'.'.$scope;
        }

        // get permission ids from database by name

        $newPermissions = Permission::whereIn('name', $newPermissions)->get()->pluck('id');

        $role->load(['permissions' => function ($query) use ($model): void {
            // Only this resource's permissions; the trailing dot keeps 'news' from also
            // matching a longer resource that starts with it.
            $query->where('name', 'like', $model.'.%');
        }]);

        $currentPermissions = $role->permissions->pluck('id');

        $permissionsToDetach = $currentPermissions->diff($newPermissions);
        $permissionsToAttach = collect($newPermissions)->diff($currentPermissions);

        $role->permissions()->detach($permissionsToDetach);
        $role->permissions()->attach($permissionsToAttach);

        // $role->syncPermissions($validated['permissions']);

        $this->clearCacheforRoleUsers($role);

        return back()->with('success', __('messages.role.permissions_updated'));
    }

    public function syncAttachableTypes(Role $role, SyncRoleAttachableTypesRequest $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validated();

        $role->attachable_types()->sync($validated['attachable_types']);

        return back()->with('success', __('messages.role.attachables_updated'));
    }

    public function syncDuties(Role $role, SyncRoleDutiesRequest $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validated();

        $role->duties()->sync($validated['duties']);

        $this->clearCacheforRoleUsers($role);

        return back()->with('success', __('messages.role.duties_updated'));
    }

    protected function clearCacheforRoleUsers(Role $role)
    {
        $role->usersThroughDuties->each(function ($user): void {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::registrationFormsCacheKey($user->id));
        });
    }
}
