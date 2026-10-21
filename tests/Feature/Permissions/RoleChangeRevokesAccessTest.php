<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

/**
 * Each change below writes pivots without a model event, so unless it resets the holders'
 * cached duties (`auth:duties:{id}`) the revoked access lingers for up to an hour.
 */
pest()->use(RefreshDatabase::class);

/**
 * The authorizer the next request would get: fresh scoped services, Spatie's permissions
 * read back from the cache store.
 */
function nextRequestAuthorizer(): ModelAuthorizer
{
    app()->forgetScopedInstances();
    app(PermissionRegistrar::class)->clearPermissionsCollection();

    return app(ModelAuthorizer::class);
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
    $this->role = Role::create(['name' => 'Revocable Role', 'guard_name' => 'web']);
    $this->role->syncPermissions([Permission::firstOrCreate(['name' => 'news.update.padalinys', 'guard_name' => 'web'])]);
    $this->user = makeUser($this->tenant);
    $this->duty = $this->user->duties()->first();
    $this->duty->assignRole($this->role);

    // Warm the persisted duty cache, as any earlier request by the holder would.
    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeTrue();
});

test('revoking a permission from a responsibility', function (): void {
    asUser($this->admin)
        ->put(route('responsibilities.permissions.update', $this->role), ['permissions' => []])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('taking a responsibility away from a duty', function (): void {
    asUser($this->admin)
        ->delete(route('responsibilities.grants.destroy', [$this->role, $this->duty->responsibilityGrants()->firstOrFail()]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('deleting a responsibility', function (): void {
    asUser($this->admin)
        ->delete(route('responsibilities.destroy', $this->role))
        ->assertRedirect();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('removing a responsibility from the duty', function (): void {
    $this->duty->responsibilityGrants()->where('role_id', $this->role->id)->get()->each->delete();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});
