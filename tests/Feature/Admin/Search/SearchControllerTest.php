<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
});

describe('unauthorized access', function (): void {
    test('guest is redirected to login', function (): void {
        $this->get(route('search.index'))
            ->assertRedirect();
    });
});

describe('authorized access', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('can access unified search page', function (): void {
        asUser($this->admin)
            ->get(route('search.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Search/SearchIndex')
                ->has('destinations.meetings')
                ->missing('can')
            );
    });

    test('every result group knows where its full list lives, and the query key that list reads', function (): void {
        asUser(makeAdminUser($this->tenant))
            ->get(route('search.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('destinations.meetings', ['href' => route('meetings.index', [], false), 'queryKey' => 'q'])
                ->where('destinations.institutions', ['href' => route('institutions.index', [], false), 'queryKey' => 'search'])
                ->where('destinations.agendaItems', ['href' => route('search.index', ['tab' => 'agenda-items'], false), 'queryKey' => 'q'])
                ->where('destinations.resources', ['href' => route('search.index', ['tab' => 'resources'], false), 'queryKey' => 'q'])
            );
    });

    test('a list the user may not open gets no link', function (): void {
        asUser(makeUser($this->tenant))
            ->get(route('search.index'))
            ->assertInertia(fn (Assert $page) => $page->where('destinations.news.href', null));
    });

    test('the agenda-items and resources tabs still render the search page', function (string $tab): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => $tab, 'q' => 'x']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Search/SearchIndex'));
    })->with(['agenda-items', 'resources']);

    test('an unknown tab from a stale bookmark falls back to the search page', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => 'nonsense']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Search/SearchIndex'));
    });
});

describe('tabs that have a page of their own', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('a Typesense collection page receives the query as q', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['q' => 'test', 'tab' => 'meetings']))
            ->assertRedirect(route('meetings.index', ['q' => 'test'], false));
    });

    test('a database table page receives the query as search', function (string $tab, string $routeName): void {
        asUser($this->admin)
            ->get(route('search.index', ['q' => 'test', 'tab' => $tab]))
            ->assertRedirect(route($routeName, ['search' => 'test'], false));
    })->with([
        ['institutions', 'institutions.index'],
        ['duties', 'duties.index'],
        ['documents', 'documents.index'],
        ['news', 'news.index'],
        ['pages', 'pages.index'],
        ['calendar', 'calendar.index'],
        ['users', 'users.index'],
    ]);

    test('the query is dropped, not sent empty, when there is none', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => 'news']))
            ->assertRedirect(route('news.index', [], false));
    });
});

describe('legacy redirects', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('search.meetings goes straight to the meetings page', function (): void {
        asUser($this->admin)
            ->get(route('search.meetings', ['q' => 'test']))
            ->assertRedirect(route('meetings.index', ['q' => 'test'], false));
    });

    test('search.institutions goes straight to the institutions page', function (): void {
        asUser($this->admin)
            ->get(route('search.institutions', ['q' => 'test']))
            ->assertRedirect(route('institutions.index', ['search' => 'test'], false));
    });

    test('search.agendaItems redirects to unified search with agenda-items tab', function (): void {
        asUser($this->admin)
            ->get(route('search.agendaItems', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'agenda-items']));
    });

    test('search.resources redirects to unified search with resources tab', function (): void {
        asUser($this->admin)
            ->get(route('search.resources', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'resources']));
    });
});
