<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
});

describe('unauthorized access', function () {
    test('guest is redirected to login', function () {
        $this->get(route('search.index'))
            ->assertRedirect();
    });
});

describe('authorized access', function () {
    beforeEach(function () {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('can access unified search page', function () {
        asUser($this->admin)
            ->get(route('search.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Search/SearchIndex')
                ->has('can.create')
            );
    });
});

describe('legacy redirects', function () {
    beforeEach(function () {
        $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    });

    test('search.meetings redirects to unified search with meetings tab', function () {
        asUser($this->admin)
            ->get(route('search.meetings', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'meetings']));
    });

    test('search.agendaItems redirects to unified search with agenda-items tab', function () {
        asUser($this->admin)
            ->get(route('search.agendaItems', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'agenda-items']));
    });

    test('search.institutions redirects to unified search with institutions tab', function () {
        asUser($this->admin)
            ->get(route('search.institutions', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'institutions']));
    });

    test('search.resources redirects to unified search with resources tab', function () {
        asUser($this->admin)
            ->get(route('search.resources', ['q' => 'test']))
            ->assertRedirect(route('search.index', ['q' => 'test', 'tab' => 'resources']));
    });
});
