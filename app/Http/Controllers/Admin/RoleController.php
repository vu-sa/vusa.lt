<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexRoleRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class RoleController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexRoleRequest $request): \Inertia\Response
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

        $roles = $query->paginate($request->input('per_page', 20))
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
    public function store(Request $request)
    {
        $this->handleAuthorization('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create($validated);

        return redirect()->route('roles.index')->with('success', 'Rolė sukurta.');
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
            return back()->with('info', 'Negalima redaguoti šios rolės.');
        }

        $role->load('permissions:id,name', 'duties:id,name');

        $tenantsWithDuties = Tenant::orderBy('shortname')->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->when(! auth()->user()?->isSuperAdmin(), function ($query) {
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
            'allAvailablePermissions' => $allAvailablePermissions->map(function ($permissions) {
                return $permissions->pluck('name');
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $this->handleAuthorization('update', $role);

        // not update Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', 'Negalima redaguoti šios rolės.');
        }

        $validated = $request->validate([
            'name' => 'required|unique:roles,name,'.$role->id,
        ]);

        $role->update($validated);

        return back()->with('success', 'Rolė atnaujinta.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->handleAuthorization('delete', $role);

        // check if role is not Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', 'Negalima ištrinti šios rolės.');
        }

        $this->clearCacheforRoleUsers($role);

        $role->delete();

        return back()->with('success', 'Rolė ištrinta.');
    }

    public function syncPermissionGroup(Role $role, string $model, Request $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validate([
            'create' => 'string',
            'read' => 'string',
            'update' => 'string',
            'delete' => 'string',
        ]);

        $newPermissions = [];

        foreach ($validated as $ability => $scope) {
            $newPermissions[] = $model.'.'.$ability.'.'.$scope;
        }

        // get permission ids from database by name

        $newPermissions = Permission::whereIn('name', $newPermissions)->get()->pluck('id');

        $role->load(['permissions' => function ($query) use ($model) {
            // query for permission names with like $model%
            $query->where('name', 'like', $model.'%');
        }]);

        $currentPermissions = $role->permissions->pluck('id');

        $permissionsToDetach = $currentPermissions->diff($newPermissions);
        $permissionsToAttach = collect($newPermissions)->diff($currentPermissions);

        $role->permissions()->detach($permissionsToDetach);
        $role->permissions()->attach($permissionsToAttach);

        // $role->syncPermissions($validated['permissions']);

        $this->clearCacheforRoleUsers($role);

        return back()->with('success', 'Rolės leidimai atnaujinti');
    }

    public function syncAttachableTypes(Role $role, Request $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validate([
            'attachable_types' => 'array',
        ]);

        $role->attachable_types()->sync($validated['attachable_types']);

        return back()->with('success', 'Rolės galimos priklausomybės atnaujintos');
    }

    public function syncDuties(Role $role, Request $request)
    {
        $this->handleAuthorization('update', $role);

        $validated = $request->validate([
            'duties' => 'array',
        ]);

        $role->duties()->sync($validated['duties']);

        $this->clearCacheforRoleUsers($role);

        return back()->with('success', 'Rolės pareigos atnaujintos');
    }

    protected function clearCacheforRoleUsers(Role $role)
    {
        $role->usersThroughDuties->each(function ($user) {
            Cache::forget('index-permissions-'.$user->id);
        });
    }
}
