<?php

use App\Models\QuickLink;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Communication Coordinator', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'quickLinks.read.padalinys',
        'quickLinks.create.padalinys',
        'quickLinks.update.padalinys',
        'quickLinks.delete.padalinys',
    ]);

    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->quickLink = QuickLink::factory()->for($this->tenant)->create([
        'text' => 'Test Quick Link',
        'link' => 'https://example.com',
        'lang' => 'lt',
        'order' => 1,
    ]);
});

describe('unauthorized access', function () {
    test('cannot access index', function () {
        asUser($this->user)
            ->get(route('quickLinks.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)
            ->get(route('quickLinks.create'))
            ->assertStatus(403);
    });

    test('cannot store quick link', function () {
        asUser($this->user)
            ->post(route('quickLinks.store'), [
                'text' => 'New Link',
                'link' => 'https://new.example.com',
            ])
            ->assertStatus(403);
    });

    test('cannot access edit page', function () {
        asUser($this->user)
            ->get(route('quickLinks.edit', $this->quickLink))
            ->assertStatus(403);
    });

    test('cannot update quick link', function () {
        asUser($this->user)
            ->patch(route('quickLinks.update', $this->quickLink), [
                'text' => 'Updated',
                'link' => 'https://updated.example.com',
            ])
            ->assertStatus(403);
    });

    test('cannot delete quick link', function () {
        asUser($this->user)
            ->delete(route('quickLinks.destroy', $this->quickLink))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('can access index', function () {
        asUser($this->admin)
            ->get(route('quickLinks.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexQuickLink')
                ->has('quickLinks')
                ->has('tenants')
                ->has('currentLang')
            );
    });

    test('can access create page', function () {
        asUser($this->admin)
            ->get(route('quickLinks.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/CreateQuickLink')
                ->has('tenantOptions')
            );
    });

    test('can store quick link with valid data', function () {
        asUser($this->admin)
            ->post(route('quickLinks.store'), [
                'text' => 'New Quick Link',
                'link' => 'https://new.example.com',
                'lang' => 'lt',
                'icon' => 'home',
                'is_important' => true,
            ])
            ->assertStatus(302)
            ->assertRedirect(route('quickLinks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quick_links', [
            'text' => 'New Quick Link',
            'link' => 'https://new.example.com',
            'lang' => 'lt',
        ]);
    });

    test('cannot store quick link without required fields', function () {
        asUser($this->admin)
            ->post(route('quickLinks.store'), [
                'text' => '',
                'link' => '',
            ])
            ->assertStatus(302)
            ->assertSessionHasErrors(['text', 'link']);
    });

    test('can access edit page', function () {
        asUser($this->admin)
            ->get(route('quickLinks.edit', $this->quickLink))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/EditQuickLink')
                ->has('quickLink')
                ->where('quickLink.id', $this->quickLink->id)
                ->has('tenantOptions')
            );
    });

    test('can update quick link', function () {
        asUser($this->admin)
            ->patch(route('quickLinks.update', $this->quickLink), [
                'text' => 'Updated Link',
                'link' => 'https://updated.example.com',
                'lang' => 'en',
                'icon' => 'star',
                'is_important' => false,
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('quick_links', [
            'id' => $this->quickLink->id,
            'text' => 'Updated Link',
            'link' => 'https://updated.example.com',
            'lang' => 'en',
        ]);
    });

    test('can delete quick link', function () {
        asUser($this->admin)
            ->delete(route('quickLinks.destroy', $this->quickLink))
            ->assertStatus(302)
            ->assertRedirect(route('quickLinks.index'))
            ->assertSessionHas('info');

        $this->assertDatabaseMissing('quick_links', [
            'id' => $this->quickLink->id,
        ]);
    });
});

describe('update order', function () {
    beforeEach(function () {
        $this->quickLink2 = QuickLink::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'order' => 2,
        ]);
        $this->quickLink3 = QuickLink::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'order' => 3,
        ]);
    });

    test('can update order of multiple quick links', function () {
        asUser($this->admin)
            ->post(route('quickLinks.update-order'), [
                'orderList' => [
                    ['id' => $this->quickLink->id, 'order' => 3],
                    ['id' => $this->quickLink2->id, 'order' => 1],
                    ['id' => $this->quickLink3->id, 'order' => 2],
                ],
                'tenant_id' => $this->tenant->id,
                'lang' => 'lt',
            ])
            ->assertStatus(302)
            ->assertRedirect(route('quickLinks.index', ['tenant' => $this->tenant->id, 'lang' => 'lt']))
            ->assertSessionHas('success');

        expect(QuickLink::find($this->quickLink->id)->order)->toBe(3);
        expect(QuickLink::find($this->quickLink2->id)->order)->toBe(1);
        expect(QuickLink::find($this->quickLink3->id)->order)->toBe(2);
    });

    test('unauthorized user cannot update order', function () {
        asUser($this->user)
            ->post(route('quickLinks.update-order'), [
                'orderList' => [
                    ['id' => $this->quickLink->id, 'order' => 99],
                ],
            ])
            ->assertStatus(403);

        expect(QuickLink::find($this->quickLink->id)->order)->toBe(1);
    });
});

describe('tenant isolation', function () {
    beforeEach(function () {
        $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();
        $this->otherQuickLink = QuickLink::factory()->for($this->otherTenant)->create([
            'lang' => 'lt',
        ]);
    });

    test('index filters quick links by tenant', function () {
        asUser($this->admin)
            ->get(route('quickLinks.index', ['tenant' => $this->tenant->id, 'lang' => 'lt']))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Content/IndexQuickLink')
                ->has('quickLinks')
                ->where('quickLinks', function ($links) {
                    return collect($links)->every(fn ($link) => $link['tenant_id'] === $this->tenant->id);
                })
            );
    });

    test('cannot edit other tenant quick link', function () {
        asUser($this->admin)
            ->get(route('quickLinks.edit', $this->otherQuickLink))
            ->assertStatus(403);
    });

    test('cannot update other tenant quick link', function () {
        asUser($this->admin)
            ->patch(route('quickLinks.update', $this->otherQuickLink), [
                'text' => 'Hacked',
                'link' => 'https://evil.com',
            ])
            ->assertStatus(403);
    });
});
