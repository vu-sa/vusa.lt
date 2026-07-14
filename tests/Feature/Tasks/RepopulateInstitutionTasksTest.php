<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

/**
 * Create an active institution with a 30-day meeting periodicity and one representative.
 */
function institutionWithRepresentative(): Institution
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();

    $institution = Institution::factory()
        ->for($tenant)
        ->create([
            'is_active' => true,
            'meeting_periodicity_days' => 30,
        ]);

    $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
        ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

    $duty = Duty::factory()
        ->for($institution)
        ->hasAttached($studentRepType, [], 'types')
        ->create();

    User::factory()->create()->duties()->attach($duty, [
        'start_date' => now()->subYear(),
        'end_date' => null,
    ]);

    return $institution;
}

function periodicityGapTaskFor(Institution $institution): ?Task
{
    return Task::query()
        ->where('taskable_type', Institution::class)
        ->where('taskable_id', $institution->id)
        ->where('action_type', ActionType::PeriodicityGap)
        ->first();
}

describe('tasks:repopulate institution', function () {
    test('creates a periodicity gap task when the institution is approaching its threshold', function () {
        // No vacation between the meeting and today: 26 calendar days of a 30-day periodicity.
        $this->travelTo('2025-11-15');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-10-20 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->not->toBeNull();
    });

    test('does not create a task when the gap is made up of vacation days', function () {
        // June 20 -> September 1 is 73 calendar days, but 62 of them are summer
        // vacation, leaving 11 effective days - well inside the 30-day periodicity.
        $this->travelTo('2025-09-01');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-06-20 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        expect(periodicityGapTaskFor($institution))->toBeNull();
    });

    test('sets a due date that does not fall inside a vacation period', function () {
        // Threshold is reached during summer vacation, so the 7-day deadline
        // is pushed past August 31 instead of landing in July.
        $this->travelTo('2025-06-28');

        $institution = institutionWithRepresentative();

        Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => '2025-06-02 10:00:00']);

        $this->artisan('tasks:repopulate institution --force')->assertExitCode(0);

        $task = periodicityGapTaskFor($institution);

        expect($task)->not->toBeNull()
            ->and($task->due_date->toDateString())->toBe('2025-09-05');
    });
});
