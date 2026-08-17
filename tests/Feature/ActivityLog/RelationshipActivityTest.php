<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->duty = Duty::factory()->for($this->institution, 'institution')->create();
});

/**
 * dutiables.start_date is NOT NULL, so a bare id-only attach() fails at the
 * DB level -- every attach in these tests needs pivot data alongside the id.
 *
 * @param  list<string>  $userIds
 * @return array<string, array{start_date: Carbon}>
 */
function dutyAttachData(array $userIds): array
{
    return collect($userIds)->mapWithKeys(fn ($id) => [$id => ['start_date' => now()]])->all();
}

test('attachAudited logs a relation_updated activity with the attached member', function (): void {
    $userA = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $userB = User::factory()->create(['name' => 'Ona Onaitė']);

    $this->duty->attachAudited('users', dutyAttachData([$userA->id, $userB->id]));

    $activity = Activity::where('subject_type', MorphMap::alias(Duty::class))
        ->where('subject_id', $this->duty->id)
        ->where('event', 'relation_updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();

    $attached = collect($activity->properties->get('attached'));
    expect($attached)->toHaveCount(2)
        ->and($attached->pluck('label')->sort()->values()->all())->toBe(['Jonas Jonaitis', 'Ona Onaitė']);
});

test('re-syncing the same members logs no activity', function (): void {
    $user = User::factory()->create();
    $this->duty->attachAudited('users', dutyAttachData([$user->id]));

    $before = Activity::where('subject_type', MorphMap::alias(Duty::class))->where('event', 'relation_updated')->count();

    $this->duty->syncAudited('users', [$user->id]);

    expect(Activity::where('subject_type', MorphMap::alias(Duty::class))->where('event', 'relation_updated')->count())->toBe($before);
});

test('detaching a member logs it under detached', function (): void {
    $user = User::factory()->create(['name' => 'Petras Petraitis']);
    $this->duty->attachAudited('users', dutyAttachData([$user->id]));

    $this->duty->detachAudited('users', [$user->id]);

    $activity = Activity::where('subject_type', MorphMap::alias(Duty::class))
        ->where('event', 'relation_updated')
        ->latest('id')
        ->first();

    $detached = collect($activity->properties->get('detached'));
    expect($detached->pluck('label')->all())->toBe(['Petras Petraitis'])
        ->and($activity->properties->get('attached'))->toBe([]);
});

test('a relation not in the allowlist throws instead of silently logging nothing', function (): void {
    expect(fn () => $this->duty->auditRelationChange('roles', fn () => null))
        ->toThrow(InvalidArgumentException::class);
});

test('a Duty <-> users sync appears on the parent Institution activity feed via root roll-up', function (): void {
    $user = User::factory()->create();

    $this->duty->attachAudited('users', dutyAttachData([$user->id]));

    $activity = Activity::where('subject_type', MorphMap::alias(Duty::class))->where('event', 'relation_updated')->latest('id')->first();

    expect($activity->root_subject_type)->toBe(MorphMap::alias(Institution::class))
        ->and($activity->root_subject_id)->toBe($this->institution->id);
});

describe('duty grants made through the user form', function (): void {
    // Attaching somebody to a duty is what places them inside a tenant, and so what
    // gives that tenant's admins authority over their record. Logged on the User via
    // AuditedRelations rather than on the Dutiable pivot, which is not an audit
    // subject and would flood the feed from the ex-officio sync.
    beforeEach(function (): void {
        $this->coordinator = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
        $this->member = User::factory()->create(['name' => 'Rasa Rasaitė']);
    });

    $latestRelationActivity = fn (User $user) => Activity::where('subject_type', MorphMap::alias(User::class))
        ->where('subject_id', $user->id)
        ->where('event', 'relation_updated')
        ->latest('id')
        ->first();

    test('granting a duty logs it against the user', function () use ($latestRelationActivity): void {
        asUser($this->coordinator)->patch(route('users.update', $this->member), [
            'name' => $this->member->name,
            'email' => $this->member->email,
            'current_duties' => [$this->duty->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $activity = $latestRelationActivity($this->member);

        expect($activity)->not->toBeNull()
            ->and(collect($activity->properties->get('attached'))->pluck('label')->all())
            ->toBe([$this->duty->name]);
    });

    test('revoking a duty is logged even though the pivot is only end-dated', function () use ($latestRelationActivity): void {
        $this->member->duties()->attach($this->duty, ['start_date' => now()->subDay()]);

        asUser($this->coordinator)->patch(route('users.update', $this->member), [
            'name' => $this->member->name,
            'email' => $this->member->email,
            'current_duties' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $activity = $latestRelationActivity($this->member);

        expect($activity)->not->toBeNull()
            ->and(collect($activity->properties->get('detached'))->pluck('label')->all())
            ->toBe([$this->duty->name]);
    });
});
