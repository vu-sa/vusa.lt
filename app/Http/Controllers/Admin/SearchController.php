<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display the unified admin search page.
     * Authorization is handled via scoped Typesense API keys at the search layer.
     */
    public function index(): InertiaResponse
    {
        $authorizer = $this->authorizer->forUser(auth()->user());

        return Inertia::render('Admin/Search/SearchIndex', [
            'can' => [
                'create' => [
                    'meetings' => $authorizer->check('meetings.create.padalinys'),
                    'institutions' => $authorizer->check('institutions.create.padalinys'),
                    'resources' => $authorizer->check('resources.create.padalinys'),
                    'duties' => $authorizer->check('duties.create.padalinys'),
                    'documents' => $authorizer->check('documents.create.padalinys'),
                    'news' => $authorizer->check('news.create.padalinys'),
                    'pages' => $authorizer->check('pages.create.padalinys'),
                    'calendar' => $authorizer->check('calendars.create.padalinys'),
                    'users' => $authorizer->check('users.create.padalinys'),
                ],
            ],
        ]);
    }

    /**
     * Redirect the legacy meetings search page to the unified search page.
     */
    public function meetings(Request $request): RedirectResponse
    {
        return $this->redirectToUnifiedSearch($request, 'meetings');
    }

    /**
     * Redirect the legacy agenda items search page to the unified search page.
     */
    public function agendaItems(Request $request): RedirectResponse
    {
        return $this->redirectToUnifiedSearch($request, 'agenda-items');
    }

    /**
     * Redirect the legacy institutions search page to the unified search page.
     */
    public function institutions(Request $request): RedirectResponse
    {
        return $this->redirectToUnifiedSearch($request, 'institutions');
    }

    /**
     * Redirect the legacy resources search page to the unified search page.
     */
    public function resources(Request $request): RedirectResponse
    {
        return $this->redirectToUnifiedSearch($request, 'resources');
    }

    /**
     * Redirect to the unified search page with the given tab, preserving query parameters.
     */
    private function redirectToUnifiedSearch(Request $request, string $tab): RedirectResponse
    {
        return redirect()->route('search.index', array_merge($request->query(), ['tab' => $tab]));
    }
}
