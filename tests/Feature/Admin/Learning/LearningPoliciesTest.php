<?php

use App\Actions\GetReceivedHandoverNotes;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\Institution;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tenant curator can only create and edit tenant steps in their own unit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.padalinys', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));
    $own = LearningStep::factory()->forTenant($tenant)->create();
    $other = LearningStep::factory()->forTenant()->create();
    $global = LearningStep::factory()->create();
    expect($user->can('create', LearningStep::class))->toBeTrue();
    expect($user->can('update', $own))->toBeTrue();
    expect($user->can('update', $other))->toBeFalse();
    expect($user->can('update', $global))->toBeFalse();
    $payload = ['kind' => 'knowledge', 'audiences' => [['type' => 'everyone', 'tenant_id' => $other->audiences->first()->tenant_id,
        'stage' => 'before_start', 'importance' => 'recommended']], 'title' => ['lt' => 'Žingsnis', 'en' => 'Step']];
    asUser($user)->post(route('learningSteps.store'), $payload)->assertForbidden();
    $payload['audiences'][0]['tenant_id'] = null;
    asUser($user)->post(route('learningSteps.store'), $payload)->assertForbidden();
    $payload['audiences'][0]['tenant_id'] = $tenant->id;
    asUser($user)->post(route('learningSteps.store'), $payload)->assertRedirect();
    $created = LearningStep::query()->where('created_by', $user->id)->sole();
    expect($created->audiences->sole()->tenant_id)->toBe($tenant->id);
});

test('global curators edit any audience but nobody deletes system steps', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.delete.*', 'web'));
    $step = LearningStep::factory()->forTenant()->create();
    $system = LearningStep::query()->where('key', 'check_access')->firstOrFail();
    expect($user->can('update', $step))->toBeTrue();
    expect($user->can('delete', $system))->toBeFalse();
    $admin = makeAdminUser(Tenant::query()->firstOrFail());
    asUser($admin)->delete(route('learningSteps.destroy', $system))->assertForbidden();
    $this->assertDatabaseHas('learning_steps', ['id' => $system->id]);
});

test('handover notes are visible to author successor holder and responsible coordinator', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $old = Duty::factory()->for($institution)->create();
    $next = Duty::factory()->for($institution)->create();
    $old->successors()->attach($next, ['effective_on' => today()]);
    $author = User::factory()->create();
    $successor = User::factory()->create();
    Dutiable::factory()->forUser($successor)->create(['duty_id' => $next->id, 'start_date' => today()->addMonth(), 'end_date' => null]);
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $outsider = makeUser(Tenant::factory()->create());
    $note = HandoverNote::factory()->for($old)->for($author, 'author')->create();

    expect($author->can('view', $note))->toBeTrue();
    expect($successor->can('view', $note))->toBeTrue();
    expect($coordinator->can('view', $note))->toBeTrue();
    expect($outsider->can('view', $note))->toBeFalse();
    expect($coordinator->can('update', $note))->toBeFalse();
    expect(GetReceivedHandoverNotes::execute($successor)->pluck('id')->all())->toContain($note->id);
    expect(GetReceivedHandoverNotes::execute($author))->toBeEmpty();
});

test('handover writing includes recent outgoing holders but excludes older and incoming terms', function (int $endedDaysAgo, bool $allowed): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    Dutiable::factory()->forUser($user)->create(['duty_id' => $duty->id, 'start_date' => today()->subYear(), 'end_date' => today()->subDays($endedDaysAgo)]);
    expect($user->can('create', [HandoverNote::class, $duty]))->toBe($allowed);
})->with(['last day' => [0, true], 'sixty days' => [60, true], 'expired' => [61, false]]);

test('a non author including a super admin cannot overwrite a handover note', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $admin = makeAdminUser($tenant);
    $note = HandoverNote::factory()->create();
    asUser($admin)->patch(route('handoverNotes.update', $note), ['situation' => '<p>Changed</p>'])->assertForbidden();
    expect($note->fresh()->situation)->toBe($note->situation);
});

test('a duty manager and incoming holder can read a handover but an unrelated member cannot edit it', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $note = HandoverNote::factory()->for($duty)->create();
    $holder = User::factory()->create();
    Dutiable::factory()->forUser($holder)->create(['duty_id' => $duty->id, 'start_date' => today()->addMonth(), 'end_date' => null]);
    $manager = makeUser($tenant);
    $manager->givePermissionTo(Permission::findOrCreate('duties.update.padalinys', 'web'));

    expect($holder->can('view', $note))->toBeTrue();
    expect($holder->can('create', [HandoverNote::class, $duty]))->toBeFalse();
    expect($manager->can('view', $note))->toBeTrue();
    asUser($holder)->patch(route('handoverNotes.update', $note), ['situation' => 'Overwrite'])->assertForbidden();
});

test('an authors old cadence stays readable but cannot be edited in a later cadence', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $old = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subYears(2), 'end_date' => today()->subYear()]);
    $current = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subMonth(), 'end_date' => today()->addYear()]);
    Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subDay(), 'end_date' => null]);
    $previous = HandoverNote::factory()->for($user, 'author')->for($duty)->create(['cadence_id' => $old->id]);
    $note = HandoverNote::factory()->for($user, 'author')->for($duty)->create(['cadence_id' => $current->id]);
    expect($user->can('view', $previous))->toBeTrue();
    expect($user->can('update', $note))->toBeTrue();
    asUser($user)->patch(route('handoverNotes.update', $previous), ['situation' => 'Overwrite'])->assertForbidden();
});

test('authors can update a recently ended cadence only during the sixty day window', function (int $days, bool $allowed): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subYear(), 'end_date' => today()->subDays($days)]);
    $note = HandoverNote::factory()->for($user, 'author')->for($duty)->create();
    expect($user->can('view', $note))->toBeTrue();
    expect($user->can('update', $note))->toBe($allowed);
})->with(['last editable day' => [60, true], 'read only' => [61, false]]);

test('coordinating a duty does not allow writing a handover as its holder', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    asUser($coordinator)->post(route('handoverNotes.store', $duty), ['situation' => 'Overwrite'])->assertForbidden();
});
