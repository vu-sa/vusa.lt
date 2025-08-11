<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

describe('Policy Debug', function () {
    test('super admin should bypass form policies', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        // Check if user has super admin role
        expect($user->hasRole('Super Admin'))->toBeTrue();
        expect($user->isSuperAdmin())->toBeTrue();

        // Test authorization directly
        $form = Form::factory()->for($tenant)->create();

        // Check authorization using Gate
        expect(Gate::forUser($user)->allows('viewAny', Form::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('view', $form))->toBeTrue();
        expect(Gate::forUser($user)->allows('create', Form::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('update', $form))->toBeTrue();
        expect(Gate::forUser($user)->allows('delete', $form))->toBeTrue();
    });

    test('super admin should access form index endpoint', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        // Test actual endpoint
        asUser($user)
            ->get(route('forms.index'))
            ->assertStatus(200);
    });

    test('super admin should bypass role policies', function () {
        $tenant = Tenant::query()->inRandomOrder()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        $role = \Spatie\Permission\Models\Role::first();

        // Check authorization using Gate
        expect(Gate::forUser($user)->allows('viewAny', \Spatie\Permission\Models\Role::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('view', $role))->toBeTrue();
        expect(Gate::forUser($user)->allows('create', \Spatie\Permission\Models\Role::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('update', $role))->toBeTrue();
        expect(Gate::forUser($user)->allows('delete', $role))->toBeTrue();
    });
});
