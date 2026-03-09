<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use App\Services\ModelAuthorizer as Authorizer;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends AdminController
{
    public function __construct(public Authorizer $authorizer) {}

    /**
     * Display the meetings search page.
     * Authorization is handled via scoped Typesense API keys at the search layer.
     */
    public function meetings(): InertiaResponse
    {
        return Inertia::render('Admin/Search/SearchMeetings', [
            'can' => [
                'create' => $this->authorizer->forUser(auth()->user())->check('meetings.create.padalinys'),
            ],
        ]);
    }

    /**
     * Display the agenda items search page.
     * Authorization is handled via scoped Typesense API keys at the search layer.
     */
    public function agendaItems(): InertiaResponse
    {
        return Inertia::render('Admin/Search/SearchAgendaItems');
    }

    /**
     * Display the institutions search page.
     * Authorization is handled via scoped Typesense API keys at the search layer.
     */
    public function institutions(): InertiaResponse
    {
        return Inertia::render('Admin/Search/SearchInstitutions', [
            'can' => [
                'create' => $this->authorizer->forUser(auth()->user())->check('institutions.create.padalinys'),
            ],
        ]);
    }
}
