<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AllowedRelationshipablesEnum;
use App\Enums\CRUDEnum;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Permission;
use Illuminate\Support\Benchmark;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Role::class, 'role');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {        
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
        return Inertia::render('Admin/Permissions/CreateRole');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
        ]);

        $role = Role::create($validated);

        return redirect()->route('roles.index')->with('success', 'Rolė sukurta.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        $role->load('permissions:id,name');
        
        // edit role
        return Inertia::render('Admin/Permissions/EditRole', [
            'role' => $role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        // $validated = $request->validate([
        //     'name' => 'required',
        //     'permissions' => 'required|array',
        // ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        //
    }

    public function syncPermissionGroup(Role $role, string $model, Request $request) 
    {
        $validated = $request->validate([
            'create' => 'string',
            'read' => 'string',
            'update' => 'string',
            'delete' => 'string',
        ]);

        $newPermissions = [];

        foreach ($validated as $ability => $scope) {
            $newPermissions[] = $model . '.' . $ability . '.' . $scope;
        }

        // get permission ids from database by name

        $newPermissions = Permission::whereIn('name', $newPermissions)->get()->pluck('id');

        $role->load(['permissions' => function($query) use ($model) {
            // query for permission names with like $model%
            $query->where('name', 'like', $model . '%');
        }]);

        $currentPermissions = $role->permissions->pluck('id');

        $permissionsToDetach = $currentPermissions->diff($newPermissions);
        $permissionsToAttach = collect($newPermissions)->diff($currentPermissions);

        $role->permissions()->detach($permissionsToDetach);
        $role->permissions()->attach($permissionsToAttach);

        // $role->syncPermissions($validated['permissions']);

        return back()->with('success', 'Rolės leidimai atnaujinti');
    }
}
