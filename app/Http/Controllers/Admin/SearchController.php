<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AdminController;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchController extends AdminController
{
    /**
     * Display the meetings search page.
     * Authorization is handled via scoped Typesense API keys at the search layer.
     */
    public function meetings(): InertiaResponse
    {
        return Inertia::render('Admin/Search/SearchMeetings');
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
        return Inertia::render('Admin/Search/SearchInstitutions');
    }
}
