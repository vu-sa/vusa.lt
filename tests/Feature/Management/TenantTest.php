<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->user = makeUser($this->tenant);
});

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index tenants', function () {
        asUser($this->user)->get(route('tenants.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access tenant create page', function () {
        asUser($this->user)->get(route('tenants.create'))->assertStatus(302);
    });

    test('can\'t store tenants', function () {
        asUser($this->user)->post(route('tenants.store'), [
            'fullname' => 'Test User',
            'shortname' => 'test',
            'type' => 'pagrindinis',
            'alias' => 'test',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\' t access the tenant edit page', function () {
        $tenant = Tenant::query()->first();

        asUser($this->user)->get(route('tenants.edit', $tenant))->assertStatus(302);
    });

    test('can\'t update tenant', function () {
        $tenant = Tenant::query()->first();

        asUser($this->user)->put(route('tenants.update', $tenant), [
            'fullname' => 'Test User Updated',
            'shortname' => 'test',
            'type' => 'pagrindinis',
            'alias' => 'test',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t delete tenant', function () {
        $tenant = Tenant::query()->first();

        asUser($this->user)->delete(route('tenants.destroy', $tenant))->assertStatus(302);
    });
});
