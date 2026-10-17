<?php

use App\Actions\UpdateDuty;
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

test('revoking a permission from a role', function (): void {
    asUser($this->admin)
        ->patch(route('roles.syncPermissionGroup', [$this->role, 'news']), ['update' => null])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('detaching a duty from a role', function (): void {
    asUser($this->admin)
        ->put(route('roles.syncDuties', $this->role), ['duties' => []])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('deleting a role', function (): void {
    asUser($this->admin)
        ->delete(route('roles.destroy', $this->role))
        ->assertRedirect();

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});

test('removing a role in the duty form', function (): void {
    app(UpdateDuty::class)->execute($this->duty->fresh(), [
        'name' => $this->duty->getTranslations('name'),
        'institution_id' => $this->duty->institution_id,
        'places_to_occupy' => $this->duty->places_to_occupy,
        'contacts_grouping' => $this->duty->contacts_grouping,
        'roles' => [],
    ]);

    expect(nextRequestAuthorizer()->allows($this->user->fresh(), 'news.update.padalinys'))->toBeFalse();
});
