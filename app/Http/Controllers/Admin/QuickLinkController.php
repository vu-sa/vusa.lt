<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetTenantsForUpserts;
use App\Enums\TenantType;
use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexQuickLinkRequest;
use App\Http\Requests\StoreQuickLinkRequest;
use App\Http\Requests\UpdateQuickLinkOrderRequest;
use App\Http\Requests\UpdateQuickLinkRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Category;
use App\Models\QuickLink;
use App\Models\Tenant;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class QuickLinkController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexQuickLinkRequest $request)
    {
        $this->handleAuthorization('viewAny', QuickLink::class);

        $tenants = GetTenantsForUpserts::execute('quickLinks.read.padalinys', $this->authorizer)
            ->filter(fn ($tenant) => in_array($tenant['type'], TenantType::representationalValues(), true))
            ->values();

        $tenantId = $request->input('tenant', $tenants->first()['id'] ?? null);
        $lang = $request->input('lang', 'lt');

        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        $showDeleted = $request->getShowDeleted();
        $deletedCount = 0;
        $quickLinks = [];

        if ($tenant) {
            $quickLinksQuery = QuickLink::query()
                ->where('tenant_id', $tenant->id)
                ->where('lang', $lang);

            $deletedCount = (clone $quickLinksQuery)->onlyTrashed()->count();

            if ($showDeleted) {
                $quickLinksQuery->onlyTrashed();
            }

            $quickLinks = $quickLinksQuery->orderBy('order')->get();
        }

        return $this->inertiaResponse('Admin/Content/IndexQuickLink', [
            'quickLinks' => $quickLinks,
            'tenant' => $tenant,
            'tenants' => $tenants,
            'currentLang' => $lang,
            'showDeleted' => $showDeleted,
            'deletedCount' => $deletedCount,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->handleAuthorization('create', QuickLink::class);

        return $this->inertiaResponse('Admin/Content/CreateQuickLink', [
            'categoryOptions' => $this->getCategoryOptions(),
            'tenantOptions' => GetTenantsForUpserts::execute('quickLinks.create.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreQuickLinkRequest $request)
    {
        $this->handleAuthorization('create', QuickLink::class);

        if (request()->user()->isSuperAdmin()) {
            $tenant_id = Tenant::main()?->id;
        } else {
            $tenant_id = $this->authorizer->permissableDuties->first()?->tenants->first()?->id;
        }

        DB::transaction(function () use ($request, $tenant_id): void {
            $quickLink = new QuickLink;
            $quickLink->fill($request->safe()->only('text', 'link', 'lang', 'icon', 'is_important'));
            $quickLink->tenant()->associate($tenant_id);
            $quickLink->save();
        });

        return redirect()->route('quickLinks.index')->with('success', $this->entityMessage('created', 'quickLink'));
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
            'categoryOptions' => $this->getCategoryOptions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateQuickLinkRequest $request, QuickLink $quickLink)
    {
        $this->handleAuthorization('update', $quickLink);

        DB::transaction(function () use ($request, $quickLink): void {
            $quickLink->update($request->safe()->only('text', 'link', 'lang', 'icon', 'is_important'));
        });

        return back()->with('success', $this->entityMessage('updated', 'quickLink'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(QuickLink $quickLink)
    {
        $this->handleAuthorization('delete', $quickLink);

        $quickLink->delete();

        return redirect()->route('quickLinks.index')->with('info', $this->entityMessage('deleted', 'quickLink'));
    }

    public function updateOrder(UpdateQuickLinkOrderRequest $request)
    {
        foreach ($request->orderList as $idAndOrder) {
            $this->handleAuthorization('update', [QuickLink::class, QuickLink::find($idAndOrder['id']), $this->authorizer]);
        }

        DB::transaction(function () use ($request): void {
            foreach ($request->orderList as $idAndOrder) {
                $quickLink = QuickLink::find($idAndOrder['id']);
                $quickLink->order = $idAndOrder['order'];
                $quickLink->save();
            }
        });

        $tenantId = $request->input('tenant_id');
        $lang = $request->input('lang', 'lt');

        return redirect()->route('quickLinks.index', [
            'tenant' => $tenantId,
            'lang' => $lang,
        ])->with('success', __('messages.quick_link.order_updated'));
    }

    /**
     * Categories aren't Typesense-searchable (7 rows repo-wide — see AGENTS.md), so the
     * link-target picker falls back to a plain list here instead of the multi-collection
     * search dialog used for pages/news/calendar events/institutions.
     *
     * Page/news/calendar/institution options used to be built the same way (an
     * unpaginated, unfiltered full-table dump per type) until the picker moved to
     * `MultiCollectionSelectDialog`, which searches those collections directly.
     *
     * @return array<int, array{id: int, name: string, alias: string|null}>
     */
    private function getCategoryOptions(): array
    {
        return Category::query()->get(['id', 'name', 'alias'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'alias' => $category->alias,
            ])
            ->all();
    }

    public function restore(QuickLink $quickLink): RedirectResponse
    {
        return $this->restoreModel($quickLink);
    }

    public function forceDelete(QuickLink $quickLink): RedirectResponse
    {
        return $this->forceDeleteModel($quickLink);
    }
}
