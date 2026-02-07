<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexBannerRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Banner;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Response as InertiaResponse;

class BannerController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexBannerRequest $request): InertiaResponse
    {
        $this->handleAuthorization('viewAny', Banner::class);

        $query = Banner::query()->with('tenant:id,shortname');

        $searchableColumns = ['title'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'banners.read.padalinys',
            ]
        );

        $banners = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/Content/IndexBanner', [
            'banners' => [
                'data' => $banners->items(),
                'meta' => [
                    'total' => $banners->total(),
                    'per_page' => $banners->perPage(),
                    'current_page' => $banners->currentPage(),
                    'last_page' => $banners->lastPage(),
                    'from' => $banners->firstItem(),
                    'to' => $banners->lastItem(),
                ],
            ],
            'filters' => $request->getFilters(),
            'sorting' => $request->getSorting(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): InertiaResponse
    {
        $this->handleAuthorization('create', Banner::class);

        return $this->inertiaResponse('Admin/Content/CreateBanner');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->handleAuthorization('create', Banner::class);

        $request->validate([
            'title' => 'required',
            'image_url' => 'required',
        ]);

        $tenants = GetTenantsForUpserts::execute('banners.create.padalinys', $this->authorizer);

        $banner = new Banner;
        // $banner->text = $request->text;
        $banner->title = $request->title;
        $banner->is_active = $request->is_active ?? 0;
        $banner->link_url = $request->link_url ?? '';
        // add random banner order for now
        $banner->order = rand(1, 10);
        $banner->image_url = $request->image_url;
        $banner->tenant_id = $tenants->first()['id'] ?? null;
        $banner->save();

        Cache::forget('banners-'.$banner->tenant_id);

        return redirect()->route('banners.index')->with('success', 'Baneris sėkmingai sukurtas!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner): InertiaResponse
    {
        $this->handleAuthorization($banner, 'update');

        return $this->inertiaResponse('Admin/Content/EditBanner', [
            'banner' => $banner,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banner $banner)
    {
        $this->handleAuthorization('update', $banner);

        $request->validate([
            'title' => 'required',
            'image_url' => 'required',
        ]);

        $banner->title = $request->title;
        $banner->is_active = $request->is_active;
        $banner->link_url = $request->link_url ?? '';
        $banner->image_url = $request->image_url;
        $banner->save();

        Cache::forget('banners-'.$banner->tenant_id);

        return $this->backResponse(['success' => 'Baneris atnaujintas!']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        $this->handleAuthorization($banner, 'delete');

        $banner->delete();

        return $this->redirectResponse('banners.index')->with('info', 'Baneris ištrintas!');
    }
}
