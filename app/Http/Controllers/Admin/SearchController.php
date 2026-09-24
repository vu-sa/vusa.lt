<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Http\Requests\IndexSearchRequest;
use App\Models\Meeting;
use App\Support\AdminSearchDestinations;
use Illuminate\Http\RedirectResponse;

class SearchController extends AdminController
{
    public function index(IndexSearchRequest $request): RedirectResponse
    {
        $tab = $request->validated('tab');
        $target = $tab !== null ? AdminSearchDestinations::pageUrl($tab, $request->validated('q')) : null;

        if ($target !== null) {
            return redirect($target);
        }

        $query = $request->validated('q');

        if (filled($query) && $request->user()->can('viewAny', Meeting::class)) {
            return redirect()->route('meetings.index', ['q' => $query]);
        }

        return redirect()->route('dashboard');
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
     * Preserve old collection links.
     */
    private function redirectToTab(IndexSearchRequest $request, string $tab): RedirectResponse
    {
        $query = $request->validated('q');

        return redirect(AdminSearchDestinations::pageUrl($tab, $query) ?? route('dashboard'));
    }
}
