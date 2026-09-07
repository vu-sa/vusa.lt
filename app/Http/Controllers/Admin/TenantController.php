<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LocaleEnum;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexTenantRequest;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateContentRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Content;
use App\Models\Institution;
use App\Models\Tenant;
use App\Services\ContentService;
use App\Services\TanstackTableService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Response;

class TenantController extends AdminController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexTenantRequest $request): Response
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

        $tenants = $query->paginate($request->getPerPage())
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

        return redirect()->route('tenants.index')->with('success', $this->entityMessage('created', 'tenant'));
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

        return redirect()->route('tenants.index')->with('success', $this->entityMessage('updated', 'tenant'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $this->handleAuthorization('delete', $tenant);

        $tenant->delete();

        return redirect()->route('tenants.index')->with('success', $this->entityMessage('deleted', 'tenant'));
    }

    public function editMainPage(Tenant $tenant)
    {
        $this->handleAuthorization('updateMainPage', $tenant);

        $locale = LocaleEnum::tryFrom(request()->string('locale')->toString()) ?? LocaleEnum::LT;
        $content = $tenant->homepageContents()
            ->where('locale', $locale->value)
            ->with('content.parts')
            ->first()?->content;

        return $this->inertiaResponse('Admin/Content/EditHomePage', [
            'tenant' => $tenant,
            'content' => $content,
            'locale' => $locale->value,
        ]);
    }

    public function updateMainPage(UpdateContentRequest $request, Tenant $tenant)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($tenant, $validated): void {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $homepageContent = $lockedTenant->homepageContents()
                ->where('locale', $validated['locale'])
                ->first();

            if ($homepageContent === null) {
                $homepageContent = $lockedTenant->homepageContents()->create([
                    'content_id' => Content::query()->create()->id,
                    'locale' => $validated['locale'],
                ]);
            }

            app(ContentService::class)->updateContentParts($homepageContent->content, $validated['parts']);
        });

        foreach (LocaleEnum::cases() as $locale) {
            Cache::tags(['homepage', "tenant_{$tenant->id}", "locale_{$locale->value}"])
                ->forget("homepage_content_{$tenant->id}_{$locale->value}");
        }

        return redirect()->back()->with('success', $this->entityMessage('updated', 'tenant'));
    }
}
