<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
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

    $activity = Activity::where('subject_type', Duty::class)
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

    $before = Activity::where('subject_type', Duty::class)->where('event', 'relation_updated')->count();

    $this->duty->syncAudited('users', [$user->id]);

    expect(Activity::where('subject_type', Duty::class)->where('event', 'relation_updated')->count())->toBe($before);
});

test('detaching a member logs it under detached', function (): void {
    $user = User::factory()->create(['name' => 'Petras Petraitis']);
    $this->duty->attachAudited('users', dutyAttachData([$user->id]));

    $this->duty->detachAudited('users', [$user->id]);

    $activity = Activity::where('subject_type', Duty::class)
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

    $activity = Activity::where('subject_type', Duty::class)->where('event', 'relation_updated')->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Institution::class)
        ->and($activity->root_subject_id)->toBe($this->institution->id);
});
