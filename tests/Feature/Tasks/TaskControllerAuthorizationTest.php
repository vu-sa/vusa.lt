<?php

use App\Models\Institution;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Student Representative Coordinator', $this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
});

/**
 * `taskable_type` used to be validated as `required` only, so any class string reached the
 * morph relation, and `tasks.create` is tenant-agnostic — meaning the taskable itself was
 * never authorized either.
 */
describe('tasks.store taskable handling', function (): void {
    test('can file a task against an institution in the user\'s own tenant', function (): void {
        asUser($this->admin)->post(route('tasks.store'), [
            'name' => 'Sutvarkyti dokumentus',
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $this->institution->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$this->admin->id],
            'separate_tasks' => false,
        ])->assertRedirect();

        expect(Task::query()->where('taskable_id', $this->institution->id)->exists())->toBeTrue();
    });

    test('rejects a taskable_type outside the allowlist', function (): void {
        asUser($this->admin)->post(route('tasks.store'), [
            'name' => 'Piktavališka užduotis',
            'taskable_type' => MorphMap::alias(Tenant::class),
            'taskable_id' => $this->tenant->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$this->admin->id],
            'separate_tasks' => false,
        ])->assertSessionHasErrors('taskable_type');

        expect(Task::query()->where('taskable_type', MorphMap::alias(Tenant::class))->exists())->toBeFalse();
    });

    test('cannot file a task against an institution in another tenant', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
        $foreignInstitution = Institution::factory()->for($otherTenant)->create();

        asUser($this->admin)->post(route('tasks.store'), [
            'name' => 'Svetima užduotis',
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $foreignInstitution->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$this->admin->id],
            'separate_tasks' => false,
        ])->assertStatus(403);

        expect(Task::query()->where('taskable_id', $foreignInstitution->id)->exists())->toBeFalse();
    });

    test('a user may always file a task on themselves', function (): void {
        asUser($this->admin)->post(route('tasks.store'), [
            'name' => 'Asmeninė užduotis',
            'taskable_type' => MorphMap::alias(User::class),
            'taskable_id' => $this->admin->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$this->admin->id],
            'separate_tasks' => false,
        ])->assertRedirect();

        expect(Task::query()->where('taskable_id', $this->admin->id)->exists())->toBeTrue();
    });

    /**
     * The guard deliberately checks `view` and not `update`: a student representative holds
     * tasks.create.padalinys with only institutions.read.own, and tasking their own
     * institution is precisely what the feature exists for.
     */
    test('a student representative can file a task on their own institution', function (): void {
        $rep = makeTenantUserWithRole('Student Representative', $this->tenant);
        $ownInstitution = $rep->duties()->first()->institution;

        asUser($rep)->post(route('tasks.store'), [
            'name' => 'Savo institucijos užduotis',
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $ownInstitution->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$rep->id],
            'separate_tasks' => false,
        ])->assertRedirect();

        expect(Task::query()->where('taskable_id', $ownInstitution->id)->exists())->toBeTrue();
    });

    test('cannot file a task on another user', function (): void {
        $other = makeUser($this->tenant);

        asUser($this->admin)->post(route('tasks.store'), [
            'name' => 'Svetima asmeninė užduotis',
            'taskable_type' => MorphMap::alias(User::class),
            'taskable_id' => $other->id,
            'due_date' => now()->addWeek()->getTimestampMs(),
            'responsible_people' => [$other->id],
            'separate_tasks' => false,
        ])->assertStatus(403);

        expect(Task::query()->where('taskable_id', $other->id)->exists())->toBeFalse();
    });
});
