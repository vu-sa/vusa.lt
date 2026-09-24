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

    test('old search URL leads to a collection page', function (): void {
        asUser($this->admin)
            ->get(route('search.index'))
            ->assertRedirect(route('dashboard'));
    });

    test('the agenda-items tab leads to its collection', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => 'agenda-items', 'q' => 'x']))
            ->assertRedirect(route('agendaItems.index', ['q' => 'x'], false));
    });

    test('agenda items have a collection page', function (): void {
        asUser(makeAdminUser($this->tenant))
            ->get(route('agendaItems.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Representation/IndexAgendaItem'));
    });

    test('agenda item collection requires meeting read access', function (): void {
        asUser(makeUser($this->tenant))
            ->get(route('agendaItems.index'))
            ->assertForbidden();
    });

    test('the resources tab goes to the resources collection', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => 'resources', 'q' => 'x']))
            ->assertRedirect(route('resources.index', ['q' => 'x'], false));
    });

    test('an unknown tab from a stale bookmark falls back to the dashboard', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['tab' => 'nonsense']))
            ->assertRedirect(route('dashboard'));
    });

    test('an old text search keeps its query on the meetings collection', function (): void {
        asUser($this->admin)
            ->get(route('search.index', ['q' => 'senatas']))
            ->assertRedirect(route('meetings.index', ['q' => 'senatas']));
    });
});

describe('tabs that have a page of their own', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('a Typesense collection page receives the query as q', function (string $tab, string $routeName): void {
        asUser($this->admin)
            ->get(route('search.index', ['q' => 'test', 'tab' => $tab]))
            ->assertRedirect(route($routeName, ['q' => 'test'], false));
    })->with([
        ['meetings', 'meetings.index'],
        ['agenda-items', 'agendaItems.index'],
        ['institutions', 'institutions.index'],
        ['resources', 'resources.index'],
        ['duties', 'duties.index'],
        ['users', 'users.index'],
    ]);

    test('a database table page receives the query as search', function (string $tab, string $routeName): void {
        asUser($this->admin)
            ->get(route('search.index', ['q' => 'test', 'tab' => $tab]))
            ->assertRedirect(route($routeName, ['search' => 'test'], false));
    })->with([
        ['documents', 'documents.index'],
        ['news', 'news.index'],
        ['pages', 'pages.index'],
        ['calendar', 'calendar.index'],
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
            ->assertRedirect(route('institutions.index', ['q' => 'test'], false));
    });

    test('search.agendaItems redirects to its collection', function (): void {
        asUser($this->admin)
            ->get(route('search.agendaItems', ['q' => 'test']))
            ->assertRedirect(route('agendaItems.index', ['q' => 'test'], false));
    });

    test('search.resources goes straight to the resources page', function (): void {
        asUser($this->admin)
            ->get(route('search.resources', ['q' => 'test']))
            ->assertRedirect(route('resources.index', ['q' => 'test'], false));
    });
});
