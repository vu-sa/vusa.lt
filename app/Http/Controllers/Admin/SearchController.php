<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSearchRequest;
use App\Services\ModelAuthorizer as Authorizer;
use App\Support\AdminSearchDestinations;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * The cross-entity search page. Authorization is handled via scoped Typesense API keys at the
     * search layer. A `?tab=` for an entity with its own page is sent there; only the tabs without
     * one (agenda items, resources) still render here.
     */
    public function index(IndexSearchRequest $request): InertiaResponse|RedirectResponse
    {
        $tab = $request->validated('tab');
        $target = $tab !== null ? AdminSearchDestinations::pageUrl($tab, $request->validated('q')) : null;

        if ($target !== null) {
            return redirect($target);
        }

        return Inertia::render('Admin/Search/SearchIndex', [
            'destinations' => AdminSearchDestinations::forUser($request->user()),
        ]);
    }

    /**
     * Legacy entry point: kept so old links and bookmarks keep working.
     */
    public function meetings(IndexSearchRequest $request): RedirectResponse
    {
        return $this->redirectToTab($request, 'meetings');
    }

    /**
     * Legacy entry point: kept so old links and bookmarks keep working.
     */
    public function agendaItems(IndexSearchRequest $request): RedirectResponse
    {
        return $this->redirectToTab($request, 'agenda-items');
    }

    /**
     * Legacy entry point: kept so old links and bookmarks keep working.
     */
    public function institutions(IndexSearchRequest $request): RedirectResponse
    {
        return $this->redirectToTab($request, 'institutions');
    }

    /**
     * Legacy entry point: kept so old links and bookmarks keep working.
     */
    public function resources(IndexSearchRequest $request): RedirectResponse
    {
        return $this->redirectToTab($request, 'resources');
    }

    /**
     * Straight to the page that now serves the tab, without a second hop through `search.index`.
     */
    private function redirectToTab(IndexSearchRequest $request, string $tab): RedirectResponse
    {
        $query = $request->validated('q');

        return redirect(
            AdminSearchDestinations::pageUrl($tab, $query)
                ?? route('search.index', array_filter(['q' => $query, 'tab' => $tab])),
        );
    }
}
