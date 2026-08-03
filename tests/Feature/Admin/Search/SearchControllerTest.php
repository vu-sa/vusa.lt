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
                ->has('can.create')
            );
    });
});

describe('legacy redirects', function (): void {
    beforeEach(function (): void {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('search.meetings redirects to unified search with meetings tab', function (): void {
        asUser($this->admin)
            ->get(route('search.meetings', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'meetings']));
    });

    test('search.agendaItems redirects to unified search with agenda-items tab', function (): void {
        asUser($this->admin)
            ->get(route('search.agendaItems', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'agenda-items']));
    });

    test('search.institutions redirects to unified search with institutions tab', function (): void {
        asUser($this->admin)
            ->get(route('search.institutions', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'institutions']));
    });

    test('search.resources redirects to unified search with resources tab', function (): void {
        asUser($this->admin)
            ->get(route('search.resources', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'resources']));
    });
});
