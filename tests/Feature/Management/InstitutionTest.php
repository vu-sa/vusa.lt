<?php

use App\Models\Institution;
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

    test('cannot index institutions', function () {
        asUser($this->user)->get(route('institutions.index'))->assertStatus(403);
    });

    test('cannot access institution create page', function () {
        asUser($this->user)->get(route('institutions.create'))->assertStatus(403);
    });

    test('cannot store institution', function () {
        asUser($this->user)->post(route('institutions.store'), [
            'name' => ['lt' => 'Test User'],
            'short_name' => ['lt' => 'test'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test',
        ])->assertStatus(403);
    });

    test('cannot access the institution edit page', function () {
        $institution = Institution::query()->first();

        asUser($this->user)->get(route('institutions.edit', $institution))->assertStatus(403);
    });

    test('cannot update institution', function () {
        $institution = Institution::query()->first();

        asUser($this->user)->put(route('institutions.update', $institution), [
            'name' => ['lt' => 'Test User'],
            'short_name' => ['lt' => 'test'],
            'tenant_id' => $this->tenant->id,
            'alias' => 'test',
        ])->assertStatus(403);
    });

    test('cannot delete institution', function () {
        $institution = Institution::query()->first();

        asUser($this->user)->delete(route('institutions.destroy', $institution))->assertStatus(403);
    });
});
