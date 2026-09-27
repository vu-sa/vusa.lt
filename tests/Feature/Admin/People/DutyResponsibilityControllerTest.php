<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $this->manager = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->member = makeUser($this->tenant);
    $this->duty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
});

function responsibilityPayload(Tenant $tenant): array
{
    return [
        'responsibility' => 'student_rep_coordination',
        'scope_type' => 'tenant',
        'scope_id' => (string) $tenant->id,
    ];
}

test('an authorized manager adds and removes a responsibility with activity records', function (): void {
    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), responsibilityPayload($this->tenant))
        ->assertRedirect();

    $assignment = $this->duty->responsibilities()->firstOrFail();

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'duty_responsibility',
        'subject_id' => $assignment->id,
        'event' => 'created',
    ]);

    asUser($this->manager)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $assignment->id]))
        ->assertRedirect();

    expect($this->duty->responsibilities()->exists())->toBeFalse();

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'duty_responsibility',
        'subject_id' => $assignment->id,
        'event' => 'deleted',
    ]);
});

test('a member without duty update permission cannot add or remove responsibilities', function (): void {
    $assignment = DutyResponsibility::factory()->for($this->duty)->forTenant($this->tenant)->create();

    asUser($this->member)
        ->post(route('duties.responsibilities.store', $this->duty), responsibilityPayload($this->tenant))
        ->assertForbidden();

    asUser($this->member)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $assignment->id]))
        ->assertForbidden();

    expect($assignment->fresh())->not->toBeNull();
});

test('a manager cannot assign a target in another padalinys or an unknown scope', function (): void {
    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), responsibilityPayload($this->otherTenant))
        ->assertSessionHasErrors('scope_id');

    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), [
            ...responsibilityPayload($this->tenant),
            'scope_id' => $this->tenant->id.'invalid',
        ])
        ->assertSessionHasErrors('scope_id');

    asUser($this->manager)
        ->post(route('duties.responsibilities.store', $this->duty), [
            ...responsibilityPayload($this->tenant),
            'scope_type' => 'role',
        ])
        ->assertSessionHasErrors('scope_type');

    expect($this->duty->responsibilities()->exists())->toBeFalse();
});

test('a responsibility id under another duty returns 404', function (): void {
    $otherDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $assignment = DutyResponsibility::factory()->for($otherDuty)->forTenant($this->tenant)->create();

    asUser($this->manager)
        ->delete(route('duties.responsibilities.destroy', [$this->duty, $assignment->id]))
        ->assertNotFound();

    expect($assignment->fresh())->not->toBeNull();
});

test('the duty page defers responsibilities and protects its optional picker', function (): void {
    DutyResponsibility::factory()->for($this->duty)->forTenant($this->tenant)->create();

    asUser($this->manager)
        ->get(route('duties.show', $this->duty))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('responsibilities')
            ->missing('responsibilityOptions')
            ->loadDeferredProps('dutyPanels', fn (Assert $deferred) => $deferred
                ->has('responsibilities.items', 1)
                ->has('responsibilities.roles')));

    asUser($this->member)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
            'X-Inertia-Partial-Component' => 'Admin/People/ShowDuty',
            'X-Inertia-Partial-Data' => 'responsibilityOptions',
        ])
        ->get(route('duties.show', $this->duty))
        ->assertRedirect()
        ->assertSessionHas('error');
});
