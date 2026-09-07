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
        // `validated()` only keeps array-shaped fields (`parts.*.options`, `json_content.*`
        // sub-values) down to the wildcard sub-keys the shared union of block-type rules
        // happens to name — a type-specific option no other block also declares (hero-carousel's
        // `scrim`/`grayscale`/`autoplay`, say) is silently dropped, not merely left unvalidated.
        // Read the already-validated request's raw `parts` instead, same as
        // PageController::update()/NewsController's `$request->content['parts']` — the rules
        // above still ran and would have failed the request on genuinely malformed input.
        $locale = $request->validated('locale');
        $parts = $request->input('parts', []);

        DB::transaction(function () use ($tenant, $locale, $parts): void {
            $lockedTenant = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $homepageContent = $lockedTenant->homepageContents()
                ->where('locale', $locale)
                ->first();

            if ($homepageContent === null) {
                $homepageContent = $lockedTenant->homepageContents()->create([
                    'content_id' => Content::query()->create()->id,
                    'locale' => $locale,
                ]);
            }

            app(ContentService::class)->updateContentParts($homepageContent->content, $parts);
        });

        foreach (LocaleEnum::cases() as $locale) {
            Cache::tags(['homepage', "tenant_{$tenant->id}", "locale_{$locale->value}"])
                ->forget("homepage_content_{$tenant->id}_{$locale->value}");
        }

        return redirect()->back()->with('success', $this->entityMessage('updated', 'tenant'));
    }
}
