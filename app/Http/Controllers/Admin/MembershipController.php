<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\StoreMembershipRequest;
use App\Http\Requests\UpdateMembershipRequest;
use App\Imports\MembershipUsersImport;
use App\Models\Membership;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Maatwebsite\Excel\Facades\Excel;

class MembershipController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->handleAuthorization('viewAny', Membership::class);

        $indexer = new ModelIndexer(new Membership);

        $memberships = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return $this->inertiaResponse('Admin/People/IndexMembership', [
            'memberships' => $memberships,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', Membership::class);

        return $this->inertiaResponse('Admin/People/CreateMembership', [
            'assignableTenants' => GetTenantsForUpserts::execute('memberships.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMembershipRequest $request)
    {
        Membership::create($request->validated());

        return redirect()->route('memberships.index')->with('success', 'Membership created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Membership $membership)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Membership $membership)
    {
        $this->handleAuthorization('update', $membership);

        return $this->inertiaResponse('Admin/People/EditMembership', [
            'membership' => $membership->toFullArray(),
            'assignableTenants' => GetTenantsForUpserts::execute('memberships.update.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMembershipRequest $request, Membership $membership)
    {
        $membership->update($request->validated());

        return back()->with('success', 'Membership updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Membership $membership)
    {
        //
    }

    public function importUsers(Membership $membership)
    {
        $this->handleAuthorization('update', $membership);

        Excel::import(new MembershipUsersImport($membership), request()->file('file'));

        return response()->json(['message' => 'Users imported.']);
    }
}
