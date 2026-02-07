<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexQuickLinkRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\TanstackTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuickLinkController extends AdminController
{
    use HasTanstackTables;

    public function __construct(public Authorizer $authorizer, private TanstackTableService $tableService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexQuickLinkRequest $request)
    {
        $this->handleAuthorization('viewAny', QuickLink::class);

        $query = QuickLink::query()->with('tenant:id,shortname');

        $searchableColumns = ['text', 'link'];

        $query = $this->applyTanstackFilters(
            $query,
            $request,
            $this->tableService,
            $searchableColumns,
            [
                'applySortBeforePagination' => true,
                'tenantRelation' => 'tenant',
                'permission' => 'quickLinks.read.padalinys',
            ]
        );

        $quickLinks = $query->paginate($request->input('per_page', 20))
            ->withQueryString();

        return $this->inertiaResponse('Admin/Content/IndexQuickLink', [
            'quickLinks' => [
                'data' => $quickLinks->items(),
                'meta' => [
                    'total' => $quickLinks->total(),
                    'per_page' => $quickLinks->perPage(),
                    'current_page' => $quickLinks->currentPage(),
                    'last_page' => $quickLinks->lastPage(),
                    'from' => $quickLinks->firstItem(),
                    'to' => $quickLinks->lastItem(),
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
        $this->handleAuthorization('create', QuickLink::class);

        return $this->inertiaResponse('Admin/Content/CreateQuickLink', [
            'typeOptions' => Inertia::lazy(fn () => $this->getQuickLinkTypeOptions(request()->input('type'))),
            'tenantOptions' => GetTenantsForUpserts::execute('quickLinks.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->handleAuthorization('create', QuickLink::class);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        if (request()->user()->isSuperAdmin()) {
            $tenant_id = Tenant::where('type', 'pagrindinis')->first()?->id;
        } else {
            $tenant_id = $this->authorizer->permissableDuties->first()?->tenants->first()?->id;
        }

        DB::transaction(function () use ($request, $tenant_id) {
            $quickLink = new QuickLink;
            $quickLink->text = $request->text;
            $quickLink->link = $request->link;
            $quickLink->lang = $request->lang;
            $quickLink->icon = $request->icon;
            $quickLink->is_important = $request->is_important;
            $quickLink->tenant()->associate($tenant_id);
            $quickLink->save();
        });

        return redirect()->route('quickLinks.index')->with('success', 'Sėkmingai sukurta greitoji nuoroda!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(QuickLink $quickLink)
    {
        $this->handleAuthorization('update', $quickLink);

        // $routes = Route::getRoutes();

        // Filter the routes to include only those without parameters EXCEPT for {subdomain} and {lang}.
        // Also the routes NOT in /mano directory, debugbar, telescope, impersonate, laravel-websockets, ignition, auth, login, feed and broadcasting routes
        // $routesWithoutParams = collect($routes->getRoutesByMethod()['GET'])->filter(function ($route) {
        //     return !collect($route->parameterNames)->except(['subdomain', 'lang'])->count() &&
        //         !collect($route->getAction())->has('prefix', 'mano') &&
        //         !collect($route->getAction())->has('prefix', '_debugbar') &&
        //         !collect($route->getAction())->has('prefix', 'telescope') &&
        //         !collect($route->getAction())->has('prefix', 'impersonate') &&
        //         !collect($route->getAction())->has('prefix', 'laravel-websockets') &&
        //         !collect($route->getAction())->has('prefix', 'ignition') &&
        //         !collect($route->getAction())->has('prefix', 'auth') &&
        //         !collect($route->getAction())->has('prefix', 'login') &&
        //         !collect($route->getAction())->has('prefix', 'feed') &&
        //         !collect($route->getAction())->has('prefix', 'broadcasting');
        // });

        // dd($routesWithoutParams);

        return $this->inertiaResponse('Admin/Content/EditQuickLink', [
            'quickLink' => $quickLink,
            'tenantOptions' => GetTenantsForUpserts::execute('quickLinks.update.padalinys', $this->authorizer),
            'typeOptions' => Inertia::lazy(fn () => $this->getQuickLinkTypeOptions(request()->input('type'))),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, QuickLink $quickLink)
    {
        $this->handleAuthorization('update', $quickLink);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        DB::transaction(function () use ($request, $quickLink) {
            $quickLink->update($request->only('text', 'link', 'lang', 'icon', 'is_important'));
        });

        return back()->with('success', 'Sėkmingai atnaujinta greitoji nuoroda!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuickLink $quickLink)
    {
        $this->handleAuthorization('delete', $quickLink);

        $quickLink->delete();

        return redirect()->route('quickLinks.index')->with('info', 'Sėkmingai ištrinta greitoji nuoroda!');
    }

    public function editOrder(Tenant $tenant, string $lang)
    {
        $quickLinks = QuickLink::query()->where('tenant_id', $tenant->id)->where('lang', $lang)->orderBy('order')->get();

        $this->handleAuthorization('update', $quickLinks->first());

        return $this->inertiaResponse('Admin/Content/EditQuickLinkOrder', [
            'quickLinks' => $quickLinks,
            'tenant' => $tenant,
        ]);
    }

    public function updateOrder(Request $request, Tenant $tenant)
    {
        $request->validate([
            'orderList' => 'required|array',
        ]);

        foreach ($request->orderList as $idAndOrder) {
            $this->handleAuthorization('update', [QuickLink::class, QuickLink::find($idAndOrder['id']), $this->authorizer]);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->orderList as $idAndOrder) {
                $quickLink = QuickLink::find($idAndOrder['id']);
                $quickLink->order = $idAndOrder['order'];
                $quickLink->save();
            }
        });

        return redirect()->route('quickLinks.index')->with('success', 'Sėkmingai atnaujinta greitųjų nuorodų tvarka!');
    }

    public static function getQuickLinkTypeOptions($type)
    {
        switch ($type) {
            case 'url':
                return;

            case 'page':
                return Page::query()->with('tenant:id,alias,shortname')->get(['id', 'lang', 'title', 'tenant_id', 'permalink']);

            case 'news':
                return News::query()->with('tenant:id,alias,shortname')->get(['id', 'lang', 'title', 'tenant_id', 'permalink']);

            case 'calendarEvent':
                return Calendar::query()->with('tenant:id,alias,shortname')->get(['id', 'title', 'tenant_id']);

            case 'institution':
                return Institution::query()->with('tenant:id,alias,shortname')->get(['id', 'name', 'tenant_id']);

                // case 'special-page':
                //     return collect();

            case 'category':
                return Category::query()->get(['id', 'name', 'alias']);

            default:
                // code...
                break;
        }
    }
}
