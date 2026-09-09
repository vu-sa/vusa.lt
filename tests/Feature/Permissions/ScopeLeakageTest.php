<?php

/**
 * Cross-permission scope leakage
 *
 * `ModelAuthorizer` used to be a request singleton carrying the state of the last check.
 * `checkAllRoleables()` reset `permissableDuties` on every call but never `isAllScope`, so
 * the first `*`-scoped permission resolved in a request latched that flag true for every
 * later resolution — and the memo then stored the latched value. `getTenants()` read the
 * flag *before* re-checking the permission it was handed, so even an explicit permission
 * argument did not help.
 *
 * These tests reproduce the two escalations that seeded roles actually made reachable.
 * Each resolves a `*`-scoped permission first, exactly as a policy check earlier in the
 * request would have, and then asserts the narrow decision is still narrow.
 *
 * @see ModelAuthorizer
 */

use App\Actions\GetTenantsForUpserts;
use App\Models\News;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Services\ResourceServices\UserDutyService;
use App\Services\TanstackTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenantA = Tenant::query()->first();
    $this->tenantB = Tenant::query()->where('id', '!=', $this->tenantA->id)->first();
    $this->authorizer = app(ModelAuthorizer::class);
});

describe('Resource Manager', function (): void {
    beforeEach(function (): void {
        // 'Resource Manager' holds resources.read.* and reservations.read.* at global
        // scope, but resources.update.padalinys only within its own tenant.
        $this->manager = makeTenantUserWithRole('Resource Manager', $this->tenantA);

        $resource = Resource::factory()->create([
            'tenant_id' => $this->tenantB->id,
            'resource_category_id' => ResourceCategory::factory()->create()->id,
        ]);

        $reservation = Reservation::factory()->create([
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
        ]);
        $reservation->resources()->attach($resource->id, [
            'quantity' => 1,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'state' => 'created',
        ]);

        $this->foreignReservationResource = ReservationResource::query()
            ->where('reservation_id', $reservation->id)
            ->where('resource_id', $resource->id)
            ->first();
    });

    test('a global read scope does not grant approval rights in another tenant', function (): void {
        // The `*`-scoped check a ReservationPolicy::view would have made first.
        expect($this->authorizer->allows($this->manager, 'reservations.read.*'))->toBeTrue();

        expect($this->foreignReservationResource->canBeApprovedBy($this->manager))->toBeFalse();
    });

    test('the manager can still approve within their own tenant', function (): void {
        $ownResource = Resource::factory()->create([
            'tenant_id' => $this->tenantA->id,
            'resource_category_id' => ResourceCategory::factory()->create()->id,
        ]);

        $reservation = Reservation::factory()->create([
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHours(2),
        ]);
        $reservation->resources()->attach($ownResource->id, [
            'quantity' => 1,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'state' => 'created',
        ]);

        $own = ReservationResource::query()
            ->where('reservation_id', $reservation->id)
            ->where('resource_id', $ownResource->id)
            ->first();

        expect($this->authorizer->allows($this->manager, 'reservations.read.*'))->toBeTrue()
            ->and($own->canBeApprovedBy($this->manager))->toBeTrue();
    });
});

describe('Global Communication Coordinator', function (): void {
    beforeEach(function (): void {
        // The real production shape: one person holding the global tag/category role
        // *and* the tenant-scoped content role. The global role is what used to latch
        // all-scope on for everything the tenant-scoped role granted.
        $this->coordinator = makeTenantUserWithRole('Global Communication Coordinator', $this->tenantA);
        $this->coordinator->duties()->first()->assignRole('Communication Coordinator');
        $this->authorizer->resetCache($this->coordinator);

        $this->actingAs($this->coordinator);
    });

    test('a global tag scope does not grant the main page of another tenant', function (): void {
        expect($this->authorizer->allows($this->coordinator, 'tags.update.*'))->toBeTrue()
            ->and(Gate::forUser($this->coordinator)->allows('updateMainPage', $this->tenantA))->toBeTrue()
            ->and(Gate::forUser($this->coordinator)->allows('updateMainPage', $this->tenantB))->toBeFalse();
    });

    test('a global tag scope does not widen the tenant picker on admin forms', function (): void {
        expect($this->authorizer->allows($this->coordinator, 'tags.update.*'))->toBeTrue();

        $tenantIds = GetTenantsForUpserts::execute('news.create.padalinys', $this->authorizer)
            ->pluck('id');

        expect($tenantIds->all())->toBe([$this->tenantA->id]);
    });

    test('a global tag scope does not unfilter an index listing', function (): void {
        $newsA = News::factory()->create(['tenant_id' => $this->tenantA->id]);
        $newsB = News::factory()->create(['tenant_id' => $this->tenantB->id]);

        expect($this->authorizer->allows($this->coordinator, 'tags.update.*'))->toBeTrue();

        $visible = app(TanstackTableService::class)
            ->applyPermissionFiltering(News::query(), 'tenant', 'news.update.padalinys', $this->authorizer)
            ->pluck('id');

        expect($visible)->toContain($newsA->id)
            ->and($visible)->not->toContain($newsB->id);
    });
});

describe('an actor holding a duty but no permission', function (): void {
    test('resolves to no tenants at all', function (): void {
        $user = makeUser($this->tenantA);

        $this->actingAs($user);

        expect($this->authorizer->tenants($user, 'users.create.padalinys'))->toBeEmpty()
            ->and(UserDutyService::getPermissableTenants($this->authorizer, 'users.create.padalinys'))->toBeEmpty();
    });
});
