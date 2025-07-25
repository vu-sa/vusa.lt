<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Role::class);

        return Inertia::render('Admin/Permissions/IndexRole', [
            'roles' => Role::paginate(20),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Role::class);

        return Inertia::render('Admin/Permissions/CreateRole');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create($validated);

        return redirect()->route('roles.index')->with('success', 'Rolė sukurta.');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        $this->authorize('view', $role);

        $role->load('permissions:id,name');

        // show role
        return Inertia::render('Admin/Permissions/ShowRole', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        $this->authorize('update', $role);

        // not load Super Admin
        if ($role->name === config('permission.super_admin_role_name')) {
            return back()->with('info', 'Negalima redaguoti šios rolės.');
        }

        $role->load('permissions:id,name', 'duties:id,name');

        $tenantsWithDuties = Tenant::orderBy('shortname')->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->when(! auth()->user()->hasRole(config('permission.super_admin_role_name')), function ($query) {
                $query->whereIn('id', User::find(Auth::id())->tenants->pluck('id'));
            })->get();

        // Get all available permissions grouped by model type
        $allAvailablePermissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);

            return $parts[0] ?? ''; // Model type (e.g., 'tags', 'news')
        });

        // edit role
        return Inertia::render('Admin/Permissions/EditRole', [
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
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

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
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

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
        $this->authorize('update', $role);

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
        $this->authorize('update', $role);

        $validated = $request->validate([
            'attachable_types' => 'array',
        ]);

        $role->attachable_types()->sync($validated['attachable_types']);

        return back()->with('success', 'Rolės galimos priklausomybės atnaujintos');
    }

    public function syncDuties(Role $role, Request $request)
    {
        $this->authorize('update', $role);

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
