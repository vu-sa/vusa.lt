<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

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

    expect($this->duty->dutiables()->where('dutiable_id', $this->member->id)->notEnded()->count())->toBe(2);
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

    expect($this->duty->dutiables()->notEnded()->where('tenant_id', $this->tenant->id)->count())->toBe(3);
});

test('the duty owner can lower a quota below the current count with the holder list omitted', function (): void {
    $this->duty->assignableTenants()->updateExistingPivot($this->tenant->id, ['quota' => 2]);

    $sourceDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $sourceDuty->exOfficioTargetDuties()->attach($this->duty);
    Dutiable::factory()->forDuty($sourceDuty)->active()->count(2)->create();
    expect($this->duty->dutiables()->whereNotNull('via_dutiable_id')->notEnded()->count())->toBe(2);

    asUser($this->owner)->patch(route('duties.update', $this->duty), [
        'name' => $this->duty->getTranslations('name'),
        'institution_id' => $this->duty->institution_id,
        'contacts_grouping' => 'tenant',
        'assignable_tenants' => [['tenant_id' => $this->tenant->id, 'quota' => 1]],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->notEnded()->count())->toBe(2)
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

    expect($this->duty->dutiables()->notEnded()->sole()->id)->toBe($returning->id);
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
        ->and($this->duty->dutiables()->notEnded()->sole()->dutiable_id)->toBe($this->member->id);
});

test('removing a temporary member cannot create a term through a full quota', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()->create();
    $this->delegate->duties()->sole()->roles()->sole()->givePermissionTo('users.create.padalinys');

    asUser($this->delegate)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => 'new-member', 'action' => 'remove']],
        'new_users' => [['temp_id' => 'new-member', 'name' => 'Naujas atstovas']],
    ])->assertSessionHasNoErrors();

    expect($this->duty->dutiables()->notEnded()->count())->toBe(1);
});

test('existing term edits reject an overlap with another manual term in the same quota', function (string $surface): void {
    $term = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)
        ->active()->create(['start_date' => today()->subMonth(), 'end_date' => today()->addWeek()]);
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)
        ->active()->create(['start_date' => today()->addWeeks(2)]);

    $end = today()->addWeeks(2)->toDateString();
    $client = asUser($this->owner);

    match ($surface) {
        'wizard' => $client->post(route('duties.batchUpdateUsers', $this->duty), [
            'tenant_id' => $this->tenant->id,
            'user_changes' => [['user_id' => $this->member->id, 'action' => 'add', 'end_date' => $end]],
        ])->assertSessionHasErrors('user_changes.0.end_date'),
        'sheet' => $client->patch(route('dutiables.update', $term), ['end_date' => $end])->assertSessionHasErrors('end_date'),
        'timeline' => $client->post(route('dutiables.timeline.apply'), [
            'operations' => [['type' => 'set_dates', 'row_ids' => [$term->id], 'end_date' => $end]],
        ])->assertSessionHasErrors('operations'),
    };

    expect($term->fresh()->end_date->toDateString())->toBe(today()->addWeek()->toDateString());
})->with(['wizard', 'sheet', 'timeline']);

test('a null start date in the wizard preserves the existing start', function (): void {
    $term = Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->active()
        ->create(['start_date' => today()->subMonth()]);
    $end = today()->addWeek()->toDateString();

    asUser($this->owner)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => $this->member->id, 'action' => 'add', 'start_date' => null, 'end_date' => $end]],
    ])->assertSessionHasNoErrors();

    expect($term->fresh()->start_date->toDateString())->toBe(today()->subMonth()->toDateString())
        ->and($term->fresh()->end_date->toDateString())->toBe($end);
});

test('the wizard rejects an end date before the resulting start date', function (string $state): void {
    if ($state !== 'new') {
        Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->create([
            'start_date' => today()->addWeek(),
            'end_date' => null,
        ]);
    }

    asUser($this->owner)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => $this->member->id, 'action' => 'add', 'end_date' => today()->subDay()->toDateString()]],
    ])->assertSessionHasErrors('user_changes.0.end_date');
})->with(['new', 'upcoming']);

test('a wizard removal before the term starts withdraws the row', function (): void {
    $term = Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->active()
        ->create(['start_date' => today()->addWeek()]);

    asUser($this->owner)->post(route('duties.batchUpdateUsers', $this->duty), [
        'user_changes' => [['user_id' => $this->member->id, 'action' => 'remove', 'end_date' => today()->toDateString()]],
    ])->assertSessionHasNoErrors();

    $this->assertModelMissing($term);
});

test('the seat quota is rechecked when a place is taken during the write', function (string $surface): void {
    $competing = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->ended()->create();
    $returning = in_array($surface, ['update', 'timeline'], true)
        ? Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->ended()->create()
        : null;
    $dutyId = $this->duty->id;
    $memberId = $this->member->id;

    Dutiable::saving(function (Dutiable $term) use ($competing, $dutyId, $memberId): void {
        if ($term->duty_id === $dutyId && $term->dutiable_id === $memberId) {
            Dutiable::whereKey($competing->id)->update(['end_date' => null]);
        }
    });

    $client = asUser($this->delegate);
    match ($surface) {
        'wizard' => $client->post(route('duties.batchUpdateUsers', $this->duty), [
            'user_changes' => [['user_id' => $memberId, 'action' => 'add']],
        ])->assertSessionHasErrors('user_changes'),
        'store' => $client->post(route('dutiables.store'), ['duty_id' => $dutyId, 'user_id' => $memberId])
            ->assertSessionHasErrors('user_id'),
        'update' => $client->patch(route('dutiables.update', $returning), ['end_date' => null])
            ->assertSessionHasErrors('end_date'),
        'timeline' => $client->post(route('dutiables.timeline.apply'), [
            'operations' => [['type' => 'set_dates', 'row_ids' => [$returning->id], 'end_date' => null]],
        ])->assertSessionHasErrors('operations'),
    };

    expect($this->duty->dutiables()->notEnded()->exists())->toBeFalse();
})->with(['wizard', 'store', 'update', 'timeline']);

test('a delegate cannot edit or delete a seat outside their quota', function (string $seat): void {
    $other = Tenant::query()->whereKeyNot($this->tenant->id)
        ->whereKeyNot($this->duty->institution->tenant_id)->firstOrFail();
    $this->duty->assignableTenants()->attach($other, ['quota' => 1]);
    $term = Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->active()->create([
        'tenant_id' => $seat === 'another quota' ? $other->id : null,
    ]);

    asUser($this->delegate)->patch(route('dutiables.update', $term), [
        'end_date' => today()->subDay()->toDateString(),
    ])->assertForbidden();
    asUser($this->delegate)->delete(route('dutiables.destroy', $term), ['stay' => true])->assertForbidden();

    expect($term->fresh()->end_date)->toBeNull();
})->with(['another quota', 'home']);

test('a delegate cannot manage a seat whose holder has been deleted', function (): void {
    $term = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->active()->create();
    $this->member->delete();

    asUser($this->delegate)->patch(route('dutiables.update', $term), [
        'end_date' => today()->subDay()->toDateString(),
    ])->assertForbidden();
    asUser($this->delegate)->delete(route('dutiables.destroy', $term), ['stay' => true])->assertForbidden();
});

test('removing an assignable tenant withdraws future terms and audits the departing holder', function (): void {
    $started = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()
        ->create(['start_date' => today()->subMonth()]);
    $future = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->active()
        ->create(['start_date' => today()->addWeek()]);

    asUser($this->owner)->patch(route('duties.update', $this->duty), [
        'name' => $this->duty->getTranslations('name'),
        'institution_id' => $this->duty->institution_id,
        'contacts_grouping' => 'tenant',
        'assignable_tenants' => [],
    ])->assertSessionHasNoErrors();

    $this->assertModelMissing($future);
    expect($started->fresh()->end_date->toDateString())->toBe(today()->subDay()->toDateString());
    $activity = Activity::forSubject($this->duty)->forEvent('relation_updated')->where('properties->relation', 'current_or_upcoming_users')->sole();
    expect(collect($activity->properties->get('detached'))->pluck('id')->all())->toEqualCanonicalizing([$started->dutiable_id, $future->dutiable_id]);
});

test('a creator without duty update permission cannot fill more than the tenant quota', function (bool $api): void {
    $creator = makeUser($this->tenant);
    $role = Role::create(['name' => 'Duty creator', 'guard_name' => 'web']);
    $role->givePermissionTo('duties.create.padalinys');
    $creator->duties()->sole()->assignRole($role);
    $payload = [
        'name' => ['lt' => 'Bendra kvotuota pareigybė', 'en' => 'Shared quota duty'],
        'institution_id' => $creator->duties()->sole()->institution_id,
        'contacts_grouping' => 'tenant',
        'assignable_tenants' => [[
            'tenant_id' => $this->tenant->id,
            'quota' => 1,
            'user_ids' => [$this->member->id, makeUser($this->tenant)->id],
        ]],
    ];

    if ($api) {
        asUser($creator)->postJson(route('api.v1.admin.duties.store'), $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('assignable_tenants.0.user_ids');
    } else {
        asUser($creator)->post(route('duties.store'), $payload)->assertSessionHasErrors('assignable_tenants.0.user_ids');
    }

    expect(Duty::where('name->lt', $payload['name']['lt'])->exists())->toBeFalse();
})->with(['web' => false, 'API' => true]);

test('a creator cannot leave a quota exceeded when another holder appears during creation', function (): void {
    $creator = makeUser($this->tenant);
    $role = Role::create(['name' => 'Duty creator', 'guard_name' => 'web']);
    $role->givePermissionTo('duties.create.padalinys');
    $creator->duties()->sole()->assignRole($role);
    $member = $this->member;
    $other = makeUser($this->tenant);
    $tenant = $this->tenant;
    $name = 'Pareigybė su viena vieta';

    Dutiable::creating(function (Dutiable $term) use ($member, $other, $tenant, $name): void {
        if ($term->dutiable_id === $member->id && $term->duty->getTranslation('name', 'lt') === $name) {
            Dutiable::factory()->forDuty($term->duty)->forTenant($tenant)->forUser($other)->active()->create();
        }
    });

    asUser($creator)->post(route('duties.store'), [
        'name' => ['lt' => $name, 'en' => 'One-seat duty'],
        'institution_id' => $creator->duties()->sole()->institution_id,
        'contacts_grouping' => 'tenant',
        'assignable_tenants' => [['tenant_id' => $tenant->id, 'quota' => 1, 'user_ids' => [$member->id]]],
    ])->assertSessionHasErrors('assignable_tenants.0.user_ids');

    expect(Duty::where('name->lt', $name)->exists())->toBeFalse();
});

test('the duty record counts manual and ex officio seats held by one person once', function (): void {
    $source = $this->member->dutiables()->sole();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->active()->create();
    Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)->active()->create([
        'via_dutiable_id' => $source->id,
    ]);

    asUser($this->owner)->get(route('duties.show', $this->duty))->assertInertia(fn ($page) => $page
        ->loadDeferredProps('dutyPanels', fn ($loaded) => $loaded->where('quotaTenants.0.occupied', 1)));
});

test('the duty record supplies the quota that a delegate would use for an existing holder', function (): void {
    Dutiable::factory()->forDuty($this->duty)->forUser($this->member)->active()->create();

    asUser($this->delegate)->get(route('duties.show', $this->duty))->assertInertia(fn ($page) => $page
        ->where('delegatedSeatTenants.'.$this->member->id, $this->tenant->id));
});

test('editing or deleting a current assignment logs the departing member once', function (string $action): void {
    $target = Duty::factory()->for($this->duty->institution)->create();
    $this->duty->exOfficioTargetDuties()->attach($target);
    $term = Dutiable::factory()->forDuty($this->duty)->forTenant($this->tenant)->forUser($this->member)
        ->active()->create(['start_date' => today()->subMonth()]);
    $client = asUser($this->delegate);
    $end = today()->subDay()->toDateString();
    $lastActivityId = Activity::query()->max('id') ?? 0;

    (match ($action) {
        'sheet' => $client->patch(route('dutiables.update', $term), ['end_date' => $end]),
        'delete' => $client->delete(route('dutiables.destroy', $term), ['stay' => true]),
        'timeline' => $client->post(route('dutiables.timeline.apply'), [
            'operations' => [['type' => 'set_dates', 'row_ids' => [$term->id], 'end_date' => $end]],
        ]),
    })->assertSessionHasNoErrors();

    foreach ([$this->duty, $target] as $duty) {
        $activity = Activity::forSubject($duty)->forEvent('relation_updated')
            ->where('properties->relation', 'current_or_upcoming_users')
            ->where('id', '>', $lastActivityId)
            ->sole();
        expect(collect($activity->properties->get('detached'))->pluck('id')->all())->toBe([$this->member->id]);
    }
})->with(['sheet', 'delete', 'timeline']);

test('one timeline save audits all departing members once on each affected duty', function (): void {
    $target = Duty::factory()->for($this->duty->institution)->create();
    $this->duty->exOfficioTargetDuties()->attach($target);
    $terms = Dutiable::factory()->forDuty($this->duty)->active()->count(2)->create(['start_date' => today()->subMonth()]);
    $lastActivityId = Activity::query()->max('id') ?? 0;

    asUser($this->owner)->post(route('dutiables.timeline.apply'), [
        'operations' => [[
            'type' => 'set_dates',
            'row_ids' => $terms->modelKeys(),
            'end_date' => today()->subDay()->toDateString(),
        ]],
    ])->assertSessionHasNoErrors();

    foreach ([$this->duty, $target] as $duty) {
        $activity = Activity::forSubject($duty)->forEvent('relation_updated')
            ->where('properties->relation', 'current_or_upcoming_users')
            ->where('id', '>', $lastActivityId)
            ->sole();
        expect(collect($activity->properties->get('detached'))->pluck('id')->all())
            ->toEqualCanonicalizing($terms->pluck('dutiable_id')->all());
    }
});
