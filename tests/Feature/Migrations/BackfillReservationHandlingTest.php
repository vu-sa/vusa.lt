<?php

use App\Enums\ResponsibilityPurpose;
use App\Jobs\ReconcileReservationReviews;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Tasks\Enums\ActionType;
use Database\Seeders\RoleReservationHandlerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

test('a pre-existing responsibility with the default name is preserved', function (): void {
    $existing = Role::forPurpose(ResponsibilityPurpose::ReservationHandling);
    $existing->update(['purpose' => null]);
    (new RoleReservationHandlerSeeder)->run();

    expect($existing->fresh()->purpose)->toBeNull()
        ->and(Role::forPurpose(ResponsibilityPurpose::ReservationHandling)->id)->not->toBe($existing->id)
        ->and(Role::forPurpose(ResponsibilityPurpose::ReservationHandling)->permissions)->toBeEmpty();
});

test('backfills current recipient duties without widening global coverage or changing permissions and rebuilds only unfinished reviews silently', function (): void {
    Bus::fake([ReconcileReservationReviews::class]);
    Notification::fake();
    $tenant = Tenant::query()->first();
    $otherTenant = Tenant::factory()->create();
    $manager = makeTenantUserWithRole('Išteklių administratorius', $tenant);
    $central = makeTenantUserWithRole('Centrinio biuro išteklių administratorius', $tenant);
    $vacant = Duty::factory()->for(Institution::factory()->for($otherTenant))->create();
    $vacant->assignRole('Išteklių administratorius');
    $resource = Resource::factory()->for($tenant)->create();
    $reservation = Reservation::factory()->create();
    $reservation->resources()->attach($resource, ['quantity' => 1, 'state' => 'created']);
    $item = ReservationResource::query()->where('reservation_id', $reservation->id)->firstOrFail();
    $reservation->tasks()->get()->each->delete();
    $deadline = now()->addDay()->startOfMinute();
    $legacy = Task::factory()->create([
        'taskable_type' => $reservation->getMorphClass(), 'taskable_id' => $reservation->id,
        'action_type' => ActionType::Approval, 'metadata' => null, 'due_date' => $deadline,
    ]);
    $legacy->users()->sync([$manager->id]);
    $completed = Task::factory()->create([
        'taskable_type' => $reservation->getMorphClass(), 'taskable_id' => $reservation->id,
        'action_type' => ActionType::Approval, 'completed_at' => now()->subDay(),
    ]);
    $completed->users()->sync([$manager->id]);
    $permissions = DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray();
    Notification::fake();

    $migration = require database_path('migrations/2026_10_25_210000_add_reservation_handling_purpose.php');
    $migration->up();
    $migration->up();

    $role = Role::forPurpose(ResponsibilityPurpose::ReservationHandling);
    $grants = $role->grants()->get();
    expect($role->permissions)->toBeEmpty()
        ->and($grants)->toHaveCount(3)
        ->and($grants->where('grantee_id', $central->current_duties()->first()->id)->sole()->scope_id)->toBe((string) $tenant->id)
        ->and($grants->where('grantee_id', $vacant->id)->sole()->scope_id)->toBe((string) $otherTenant->id)
        ->and(DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray())->toEqual($permissions)
        ->and($legacy->fresh())->toBeNull()
        ->and($completed->fresh()->completed_at)->not->toBeNull()
        ->and($completed->fresh()->users->modelKeys())->toBe([$manager->id]);
    $rebuilt = $reservation->tasks()->whereNull('completed_at')->sole();
    expect($rebuilt->metadata)->toMatchArray(['reservation_resource_id' => $item->id, 'approval_step' => 1])
        ->and($rebuilt->due_date->equalTo($deadline))->toBeTrue()
        ->and($rebuilt->users->modelKeys())->toEqualCanonicalizing([$manager->id, $central->id]);
    Notification::assertNothingSent();
});
