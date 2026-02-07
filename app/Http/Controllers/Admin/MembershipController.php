<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexMembershipRequest;
use App\Http\Requests\StoreMembershipRequest;
use App\Http\Requests\UpdateMembershipRequest;
use App\Http\Traits\HasTanstackTables;
use App\Imports\MembershipUsersImport;
use App\Models\Membership;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Maatwebsite\Excel\Facades\Excel;

class MembershipController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexMembershipRequest $request)
    {
        $this->handleAuthorization('viewAny', Membership::class);

        $query = Membership::query()->with(['tenant:id,shortname']);

        $searchableColumns = ['name'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'tenantRelation' => 'tenant',
                'permission' => 'memberships.read.padalinys',
            ]
        );

        $memberships = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        return $this->inertiaResponse('Admin/People/IndexMembership', [
            'memberships' => [
                'data' => $memberships->getCollection()->map->toFullArray()->values(),
                'meta' => [
                    'total' => $memberships->total(),
                    'per_page' => $memberships->perPage(),
                    'current_page' => $memberships->currentPage(),
                    'last_page' => $memberships->lastPage(),
                    'from' => $memberships->firstItem(),
                    'to' => $memberships->lastItem(),
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
