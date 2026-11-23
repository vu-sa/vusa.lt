<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $owner = Tenant::query()->firstOrFail();
    $this->tenant = Tenant::query()->whereKeyNot($owner->id)->firstOrFail();
    $this->duty = Duty::factory()->for(Institution::factory()->for($owner))->create();
    $this->duty->assignableTenants()->attach($this->tenant, ['quota' => 1]);
    $this->delegate = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->owner = makeAdminUser($owner);
    $this->member = makeUser($this->tenant);
});

test('a delegated admin cannot resume a term when all seats are taken', function (bool $timeline): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->create();
    $ended = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->ended()->create();
    $originalEnd = $ended->end_date->toDateString();

    if ($timeline) {
        asUser($this->delegate)->post(route('dutiables.timeline.apply'), [
            'operations' => [['type' => 'set_dates', 'row_ids' => [$ended->id], 'end_date' => null]],
        ])->assertSessionHasErrors('operations');
    } else {
        asUser($this->delegate)->patch(route('dutiables.update', $ended), ['end_date' => null])
            ->assertSessionHasErrors('end_date');
    }

    expect($ended->fresh()->end_date->toDateString())->toBe($originalEnd);
})->with(['assignment form' => false, 'timeline' => true]);

test('assigning a newly created member to a quota warns that the member is outside that tenant', function (): void {
    $name = 'Naujas atstovas';

    asUser($this->owner)->post(route('dutiables.store'), [
        'duty_id' => $this->duty->id,
        'new_user' => ['name' => $name],
        'tenant_id' => $this->tenant->id,
    ])->assertSessionHasNoErrors()->assertSessionHas('toast_description', __('dutiables.assign.quota_tenant_not_member', [
        'name' => $name,
        'tenant' => $this->tenant->shortname,
    ]));

    expect($this->duty->dutiables()->sole()->user->name)->toBe($name);
});

test('the owning admin can choose an assignable tenant and exceed its quota in the batch', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->create();
    $url = route('duties.batchUpdateUsers', $this->duty);
    $changes = ['user_changes' => [['user_id' => $this->member->id, 'action' => 'add']]];

    asUser($this->owner)->post($url, [...$changes, 'tenant_id' => $this->duty->institution->tenant_id])
        ->assertSessionHasErrors('tenant_id');

    asUser($this->owner)->post($url, [...$changes, 'tenant_id' => $this->tenant->id])
        ->assertSessionHasNoErrors();

    $seat = $this->duty->dutiables()->where('dutiable_id', $this->member->id)->sole();
    expect($seat->tenant_id)->toBe($this->tenant->id)
        ->and($seat->start_date->toDateString())->toBe(today()->toDateString());
});

test('a batch rejects repeated holders and an end date before its submitted start date', function (): void {
    $url = route('duties.batchUpdateUsers', $this->duty);
    $add = ['user_id' => $this->member->id, 'action' => 'add'];

    asUser($this->owner)->post($url, ['user_changes' => [$add, [...$add, 'action' => 'remove']]])
        ->assertSessionHasErrors('user_changes.1.user_id');

    asUser($this->owner)->post($url, ['user_changes' => [[
        ...$add, 'start_date' => '2026-11-24', 'end_date' => '2026-11-23',
    ]]])->assertSessionHasErrors('user_changes.0.end_date');

    expect($this->duty->dutiables()->exists())->toBeFalse();
});

test('the batch quota checks only the acting tenant', function (): void {
    $other = Tenant::query()->whereKeyNot($this->duty->institution->tenant_id)
        ->whereKeyNot($this->tenant->id)->firstOrFail();
    $otherAdminDuty = Duty::factory()->for(Institution::factory()->for($other))->create();
    $otherAdminDuty->assignRole('Komunikacijos koordinatorius');
    $this->delegate->duties()->attach($otherAdminDuty, ['start_date' => today()]);
    $this->duty->assignableTenants()->attach($other, ['quota' => 1]);
    Dutiable::factory()->forDuty($this->duty)->forTenant($other)->active()->create();

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'tenant_id' => $this->tenant->id,
        'user_changes' => [['user_id' => $this->member->id, 'action' => 'add']],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->where('dutiable_id', $this->member->id)->sole()->tenant_id)->toBe($this->tenant->id);
});

test('a manual overlap only checks the tenant whose seat is being assigned', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->active()->create();

    asUser($this->delegate)->post(route('dutiables.store'), [
        'duty_id' => $this->duty->id,
        'user_id' => $this->member->id,
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->where('dutiable_id', $this->member->id)->pluck('tenant_id')->all())
        ->toEqualCanonicalizing([null, $this->tenant->id]);
});

test('an ex-officio holder can also hold a manual term in the same quota', function (): void {
    $source = $this->member->dutiables()->sole();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->active()->create([
        'via_dutiable_id' => $source->id,
    ]);

    asUser($this->delegate)->post(route('dutiables.store'), [
        'duty_id' => $this->duty->id,
        'user_id' => $this->member->id,
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->where('dutiable_id', $this->member->id)->current()->count())->toBe(2);
});

test('a batch removal does not free a seat still held ex officio', function (): void {
    $holder = makeUser($this->tenant);
    $source = $holder->dutiables()->sole();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($holder)->active()->create();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($holder)->active()->create([
        'via_dutiable_id' => $source->id,
    ]);

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $holder->id, 'action' => 'remove'],
            ['user_id' => $this->member->id, 'action' => 'add'],
        ],
    ])->assertSessionHasErrors('user_changes');
});

test('a tenant quota counts people once even when they hold multiple terms', function (): void {
    $this->duty->assignableTenants()->updateExistingPivot($this->tenant->id, ['quota' => 2]);
    $holder = makeUser($this->tenant);
    $source = $holder->dutiables()->sole();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($holder)->active()->create();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($holder)->active()->create([
        'via_dutiable_id' => $source->id,
    ]);

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => $this->member->id, 'action' => 'add']],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->current()->where('tenant_id', $this->tenant->id)->count())->toBe(3);
});

test('the duty owner can lower a quota below the current count with the holder list omitted', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->count(2)->create();

    asUser($this->owner)->patch(route('duties.update', $this->duty), [
        'name' => $this->duty->getTranslations('name'),
        'institution_id' => $this->duty->institution_id,
        'contacts_grouping' => 'tenant',
        'assignable_tenants' => [['tenant_id' => $this->tenant->id, 'quota' => 1]],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->current()->count())->toBe(2)
        ->and($this->duty->assignableTenants()->sole()->pivot->quota)->toBe(1);
});

test('a delegated admin can reuse a seat freed in the same timeline save', function (): void {
    $departing = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser(makeUser($this->tenant))->active()->create([
        'start_date' => today()->subMonth(),
    ]);
    $returning = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->ended()->create();

    asUser($this->delegate)->post(route('dutiables.timeline.apply'), [
        'operations' => [
            ['type' => 'set_dates', 'row_ids' => [$returning->id], 'end_date' => null],
            ['type' => 'set_dates', 'row_ids' => [$departing->id], 'end_date' => today()->subDay()->toDateString()],
        ],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->current()->sole()->id)->toBe($returning->id);
});

test('the duty owner can resume a term beyond a full quota', function (bool $timeline): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->create();
    $ended = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->ended()->create();

    if ($timeline) {
        asUser($this->owner)->post(route('dutiables.timeline.apply'), [
            'operations' => [['type' => 'set_dates', 'row_ids' => [$ended->id], 'end_date' => null]],
        ])->assertSessionHasNoErrors();
    } else {
        asUser($this->owner)->patch(route('dutiables.update', $ended), ['end_date' => null])
            ->assertSessionHasNoErrors();
    }

    expect($ended->fresh()->end_date)->toBeNull();
})->with(['assignment form' => false, 'timeline' => true]);

test('a batch can reuse a seat when its last day was yesterday', function (): void {
    $leaving = makeUser($this->tenant);
    $seat = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($leaving)->active()
        ->create(['start_date' => today()->subMonth()]);

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [
            ['user_id' => $leaving->id, 'action' => 'remove', 'end_date' => today()->subDay()->toDateString()],
            ['user_id' => $this->member->id, 'action' => 'add'],
        ],
    ])->assertSessionHasNoErrors();

    expect($seat->fresh()->end_date->toDateString())->toBe(today()->subDay()->toDateString())
        ->and($this->duty->dutiables()->current()->sole()->dutiable_id)->toBe($this->member->id);
});

test('removing a temporary member cannot create a term through a full quota', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->create();
    $this->delegate->duties()->sole()->roles()->sole()->givePermissionTo('users.create.padalinys');

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => 'new-member', 'action' => 'remove']],
        'new_users' => [['temp_id' => 'new-member', 'name' => 'Naujas atstovas']],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->current()->count())->toBe(1);
});
