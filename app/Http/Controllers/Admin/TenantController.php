<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\LaravelResourceController;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Institution;
use App\Models\Tenant;
use Inertia\Inertia;

class TenantController extends LaravelResourceController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', [Tenant::class, $this->authorizer]);

        $tenants = Tenant::query()->paginate(15);

        // also check if empty array
        return Inertia::render('Admin/People/IndexTenant', [
            'tenants' => $tenants,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', [Tenant::class, $this->authorizer]);

        return Inertia::render('Admin/People/CreateTenant', [
            'assignableInstitutions' => Institution::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        $tenant = new Tenant();

        $tenant->fill($request->validated());

        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        $this->authorize('update', [$tenant, $this->authorizer]);

        return Inertia::render('Admin/People/EditTenant', [
            'tenant' => $tenant,
            'assignableInstitutions' => Institution::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->fill($request->validated());

        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $this->authorize('delete', [$tenant, $this->authorizer]);

        $tenant->delete();

        return redirect()->route('tenants.index')->with('success', 'Tenant deleted.');
    }
}
