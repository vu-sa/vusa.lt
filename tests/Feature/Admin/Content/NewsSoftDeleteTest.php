<?php

use App\Models\News;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

    $this->role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $this->role->givePermissionTo([
        'news.read.padalinys',
        'news.create.padalinys',
        'news.update.padalinys',
        'news.delete.padalinys',
    ]);

    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->live = News::factory()->for($this->tenant)->create(['title' => 'Live article']);
    $this->trashed = News::factory()->for($this->tenant)->create(['title' => 'Trashed article']);
    $this->trashed->delete();
});

/**
 * Grants an extra permission to the coordinator role and flushes both permission
 * caches, otherwise the authorizer keeps answering from the pre-grant snapshot.
 */
function grantNewsPermission(string $permission): void
{
    test()->role->givePermissionTo($permission);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    cache()->flush();
}

describe('trashed index view', function () {
    test('index hides soft-deleted records by default', function () {
        $response = asUser($this->admin)->get(route('news.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('showDeleted', false)
                ->has('news.data')
            );

        $ids = collect($response->viewData('page')['props']['news']['data'])->pluck('id');

        expect($ids)->toContain($this->live->id)
            ->and($ids)->not->toContain($this->trashed->id);
    });

    // Regression for the withTrashed()/method_exists() bug that made this toggle a no-op.
    test('index returns only soft-deleted records when showDeleted is true', function () {
        $response = asUser($this->admin)->get(route('news.index', ['showDeleted' => 'true']));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('showDeleted', true));

        $ids = collect($response->viewData('page')['props']['news']['data'])->pluck('id');

        expect($ids)->toContain($this->trashed->id)
            ->and($ids)->not->toContain($this->live->id);
    });

    test('index exposes the deleted record count', function () {
        asUser($this->admin)
            ->get(route('news.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    });

    // The row actions decide whether to offer Restore / Delete permanently purely from
    // deleted_at, so it must survive serialization into the Inertia payload.
    test('trashed rows carry deleted_at so the row actions can render', function () {
        $response = asUser($this->admin)->get(route('news.index', ['showDeleted' => 'true']));

        $rows = collect($response->viewData('page')['props']['news']['data']);

        expect($rows)->toHaveCount(1)
            ->and($rows->first())->toHaveKey('deleted_at')
            ->and($rows->first()['deleted_at'])->not->toBeNull();
    });

    test('deleted count only counts records the user may see', function () {
        $foreign = News::factory()->for($this->otherTenant)->create();
        $foreign->delete();

        asUser($this->admin)
            ->get(route('news.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    });
});

describe('restore', function () {
    test('a user without delete permission cannot restore', function () {
        asUser($this->user)
            ->patch(route('news.restore', $this->trashed->id))
            ->assertStatus(403);

        expect($this->trashed->fresh()->trashed())->toBeTrue();
    });

    test('the delete permission is enough to restore', function () {
        asUser($this->admin)
            ->patch(route('news.restore', $this->trashed->id))
            ->assertRedirect();

        expect($this->trashed->fresh()->trashed())->toBeFalse();
    });

    test('cannot restore a record from another tenant', function () {
        $foreign = News::factory()->for($this->otherTenant)->create();
        $foreign->delete();

        asUser($this->admin)
            ->patch(route('news.restore', $foreign->id))
            ->assertStatus(403);

        expect($foreign->fresh()->trashed())->toBeTrue();
    });
});

describe('force delete', function () {
    test('the delete permission alone does not allow permanent deletion', function () {
        asUser($this->admin)
            ->delete(route('news.forceDelete', $this->trashed->id))
            ->assertStatus(403);

        expect(News::withTrashed()->find($this->trashed->id))->not->toBeNull();
    });

    test('the forceDelete permission allows permanent deletion', function () {
        grantNewsPermission('news.forceDelete.padalinys');

        asUser($this->admin)
            ->delete(route('news.forceDelete', $this->trashed->id))
            ->assertRedirect();

        expect(News::withTrashed()->find($this->trashed->id))->toBeNull();
    });

    test('a record that is not deleted yet cannot be permanently deleted', function () {
        grantNewsPermission('news.forceDelete.padalinys');

        asUser($this->admin)
            ->delete(route('news.forceDelete', $this->live->id))
            ->assertStatus(403);

        expect(News::find($this->live->id))->not->toBeNull();
    });

    test('cannot permanently delete a record from another tenant', function () {
        grantNewsPermission('news.forceDelete.padalinys');

        $foreign = News::factory()->for($this->otherTenant)->create();
        $foreign->delete();

        asUser($this->admin)
            ->delete(route('news.forceDelete', $foreign->id))
            ->assertStatus(403);

        expect(News::withTrashed()->find($foreign->id))->not->toBeNull();
    });

    test('a user with no permissions at all cannot permanently delete', function () {
        asUser($this->user)
            ->delete(route('news.forceDelete', $this->trashed->id))
            ->assertStatus(403);

        expect(News::withTrashed()->find($this->trashed->id))->not->toBeNull();
    });
});

describe('permission seeding', function () {
    test('forceDelete permissions exist only for soft-deletable models', function () {
        $names = Permission::query()
            ->where('name', 'like', '%.forceDelete.%')
            ->pluck('name');

        expect($names)->toContain('news.forceDelete.padalinys')
            ->and($names)->toContain('news.forceDelete.*')
            ->and($names)->not->toContain('tasks.forceDelete.padalinys')
            ->and($names)->not->toContain('tenants.forceDelete.*');
    });

    test('forceDelete is never granted at the own scope', function () {
        expect(
            Permission::query()->where('name', 'like', '%.forceDelete.own')->count()
        )->toBe(0);
    });
});
