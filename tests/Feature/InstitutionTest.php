<?php

use App\Models\Institution;
use App\Models\Tenant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    $this->user = makeUser($this->tenant);
});

describe('auth: simple user', function () {
    beforeEach(function () {
        asUser($this->user)->get(route('dashboard'))->assertStatus(200);
    });

    test('can\'t index institutions', function () {
        asUser($this->user)->get(route('institutions.index'))->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t access institution create page', function () {
        asUser($this->user)->get(route('institutions.create'))->assertStatus(302);
    });

    test('can\'t store institution', function () {
        asUser($this->user)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test User'],
            'short_name' => ['lt' => 'test'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\' t access the institution edit page', function () {
        $institution = Institution::query()->first();

        asUser($this->user)->get(route('institutions.edit', $institution))->assertStatus(302);
    });

    test('can\'t update institutions', function () {
        $tenant = Institution::query()->first();

        asUser($this->user)->put(route('institutions.update', $tenant), [
            'name' => ['lt' => 'Test User'],
            'short_name' => ['lt' => 'test'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test',
        ])->assertStatus(302)->assertRedirectToRoute('dashboard');
    });

    test('can\'t delete institution', function () {
        $tenant = Institution::query()->first();

        asUser($this->user)->delete(route('institutions.destroy', $tenant))->assertStatus(302);
    });
});
