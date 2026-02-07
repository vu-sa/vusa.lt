<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTenantRequest;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Content;
use App\Models\Institution;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Support\Facades\Cache;

class TenantController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTenantRequest $request): \Inertia\Response
    {
        $this->handleAuthorization('viewAny', Tenant::class);

        $query = Tenant::query();

        $searchableColumns = ['fullname', 'shortname', 'alias'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
            ]
        );

        $tenants = $query->paginate($request->input('per_page', 15))
            ->withQueryString();

        $sorting = $request->getSorting();

        return $this->inertiaResponse('Admin/People/IndexTenant', [
            'tenants' => [
                'data' => $tenants->items(),
                'meta' => [
                    'total' => $tenants->total(),
                    'per_page' => $tenants->perPage(),
                    'current_page' => $tenants->currentPage(),
                    'last_page' => $tenants->lastPage(),
                    'from' => $tenants->firstItem(),
                    'to' => $tenants->lastItem(),
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
        $this->handleAuthorization('create', Tenant::class);

        return $this->inertiaResponse('Admin/People/CreateTenant', [
            'assignableInstitutions' => Institution::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        $tenant = new Tenant;

        $tenant->fill($request->validated());

        $tenant->save();

        return redirect()->route('tenants.index')->with('success', 'Tenant created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        $this->handleAuthorization('update', $tenant);

        return $this->inertiaResponse('Admin/People/EditTenant', [
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
        $this->handleAuthorization('delete', $tenant);

        $tenant->delete();

        return redirect()->route('tenants.index')->with('success', 'Tenant deleted.');
    }

    public function editMainPage(Tenant $tenant)
    {
        $this->handleAuthorization('updateMainPage', $tenant);

        $tenant->load('content.parts');

        if ($tenant->content === null) {
            $content = new Content;
            $content->save();
            $tenant->content()->associate($content)->save();
        }

        return $this->inertiaResponse('Admin/Content/EditHomePage', [
            'tenant' => $tenant->load('content.parts'),
        ]);
    }

    public function updateMainPage(UpdateContentRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        $content = Content::query()->find($validated['id']);

        // Use ContentService to efficiently update content parts
        app(\App\Services\ContentService::class)->updateContentParts($content, $validated['parts']);

        // Clear homepage cache for this tenant (both locales)
        Cache::tags(['homepage', "tenant_{$tenant->id}"])->flush();

        return redirect()->back()->with('success', 'Tenant updated.');
    }
}
