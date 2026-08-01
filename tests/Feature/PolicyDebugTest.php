<?php

use App\Models\Form;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

pest()->use(RefreshDatabase::class);

describe('Policy Debug', function (): void {
    test('super admin should bypass form policies', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        // Check if user has super admin role
        expect($user->hasRole('Super Admin'))->toBeTrue();
        expect($user->isSuperAdmin())->toBeTrue();

        // Test authorization directly
        $form = Form::factory()->for($tenant)->create();

        // Check authorization using Gate
        expect(Gate::forUser($user)->allows('viewAny', Form::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('view', $form))->toBeTrue()
            ->and(Gate::forUser($user)->allows('create', Form::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('update', $form))->toBeTrue()
            ->and(Gate::forUser($user)->allows('delete', $form))->toBeTrue();
    });

    test('super admin should access form index endpoint', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        // Test actual endpoint
        asUser($user)
            ->get(route('forms.index'))
            ->assertStatus(200);
    });

    test('super admin should bypass role policies', function (): void {
        $tenant = Tenant::query()->first();
        $user = makeUser($tenant);
        $user->assignRole('Super Admin');

        $role = Role::first();

        // Check authorization using Gate
        expect(Gate::forUser($user)->allows('viewAny', Role::class))->toBeTrue();
        expect(Gate::forUser($user)->allows('view', $role))->toBeTrue()
            ->and(Gate::forUser($user)->allows('create', Role::class))->toBeTrue()
            ->and(Gate::forUser($user)->allows('update', $role))->toBeTrue()
            ->and(Gate::forUser($user)->allows('delete', $role))->toBeTrue();
    });
});
