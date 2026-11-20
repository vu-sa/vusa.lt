<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $meeting = Meeting::factory()->hasAttached($this->institution)->create();
    $this->task = Task::factory()->forMeeting($meeting)->create();
    $this->reader = User::factory()->create();
});

function attachTaskPolicyDuty(User $user, Institution $institution, array $permissions, array $term = []): void
{
    $duty = Duty::factory()->for($institution)->create();
    $duty->givePermissionTo($permissions);
    $user->duties()->attach($duty, [
        'start_date' => now()->subMonth(),
        'end_date' => null,
        ...$term,
    ]);
}

test('a current duty granting tenant task and meeting read permissions allows viewing a meeting task', function (): void {
    attachTaskPolicyDuty($this->reader, $this->institution, ['tasks.read.padalinys', 'meetings.read.padalinys']);

    expect(Gate::forUser($this->reader)->allows('view', $this->task))->toBeTrue();
});

test('an ended duty granting meeting read permission does not allow viewing a meeting task', function (): void {
    attachTaskPolicyDuty($this->reader, $this->institution, ['tasks.read.padalinys']);
    attachTaskPolicyDuty($this->reader, $this->institution, ['tasks.read.padalinys', 'meetings.read.padalinys'], [
        'start_date' => now()->subYears(3),
        'end_date' => now()->subYears(2),
    ]);

    expect(Gate::forUser($this->reader)->allows('view', $this->task))->toBeFalse();
});

test('a current duty with global meeting read permission allows viewing a meeting task in another tenant', function (): void {
    $other = Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail();
    $otherInstitution = Institution::factory()->for($other)->create();
    attachTaskPolicyDuty($this->reader, $otherInstitution, ['tasks.read.padalinys', 'meetings.read.*']);

    expect(Gate::forUser($this->reader)->allows('view', $this->task))->toBeTrue();
});

test('a direct tenant meeting read permission and a current duty allow viewing a meeting task', function (): void {
    attachTaskPolicyDuty($this->reader, $this->institution, ['tasks.read.padalinys']);
    $this->reader->givePermissionTo('meetings.read.padalinys');

    expect(Gate::forUser($this->reader)->allows('view', $this->task))->toBeTrue();
});
