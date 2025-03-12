<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\QuickLink;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\ModelIndexer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuickLinkController extends Controller
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', QuickLink::class);

        $indexer = new ModelIndexer(new QuickLink);

        $quickLinks = $indexer
            ->setEloquentQuery()
            ->filterAllColumns()
            ->sortAllColumns()
            ->builder->paginate(15);

        return Inertia::render('Admin/Content/IndexQuickLink', [
            'quickLinks' => $quickLinks,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', QuickLink::class);

        return Inertia::render('Admin/Content/CreateQuickLink', [
            'typeOptions' => Inertia::lazy(fn () => $this->getQuickLinkTypeOptions(request()->input('type'))),
            'tenantOptions' => GetTenantsForUpserts::execute('quickLinkss.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', QuickLink::class);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        if (request()->user()->hasRole(config('permission.super_admin_role_name'))) {
            $tenant_id = Tenant::where('type', 'pagrindinis')->first()->id;
        } else {
            $tenant_id = $this->authorizer->permissableDuties->first()->tenants->first()->id;
        }

        DB::transaction(function () use ($request, $tenant_id) {
            $quickLinks = new QuickLink;
            $quickLinks->text = $request->text;
            $quickLinks->link = $request->link;
            $quickLinks->lang = $request->lang;
            $quickLinks->icon = $request->icon;
            $quickLinks->is_important = $request->is_important;
            $quickLinks->tenant()->associate($tenant_id);
            $quickLinks->save();
        });

        return redirect()->route('quickLinks.index')->with('success', 'Sėkmingai sukurta greitoji nuoroda!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(QuickLink $quickLink)
    {
        $this->authorize('update', $quickLink);

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

        return Inertia::render('Admin/Content/EditQuickLink', [
            'quickLink' => $quickLink,
            'tenantOptions' => GetTenantsForUpserts::execute('quickLinks.update.padalinys', $this->authorizer),
            'typeOptions' => Inertia::lazy(fn () => $this->getQuickLinkTypeOptions(request()->input('type'))),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, QuickLink $quickLinks)
    {
        $this->authorize('update', $quickLinks);

        $request->validate([
            'text' => 'required',
            'link' => 'required',
        ]);

        DB::transaction(function () use ($request, $quickLinks) {
            $quickLinks->update($request->only('text', 'link', 'lang', 'icon', 'is_important'));
        });

        return back()->with('success', 'Sėkmingai atnaujinta greitoji nuoroda!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(QuickLink $quickLinks)
    {
        $this->authorize('delete', $quickLinks);

        $quickLinks->delete();

        return redirect()->route('quickLinks.index')->with('info', 'Sėkmingai ištrinta greitoji nuoroda!');
    }

    public function editOrder(Tenant $tenant, string $lang)
    {
        $quickLinks = QuickLink::query()->where('tenant_id', $tenant->id)->where('lang', $lang)->orderBy('order')->get();

        $this->authorize('update', $quickLinks->first());

        return Inertia::render('Admin/Content/EditQuickLinkOrder', [
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
            $this->authorize('update', [QuickLink::class, QuickLink::find($idAndOrder['id']), $this->authorizer]);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->orderList as $idAndOrder) {
                $quickLinks = QuickLink::find($idAndOrder['id']);
                $quickLinks->order = $idAndOrder['order'];
                $quickLinks->save();
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
