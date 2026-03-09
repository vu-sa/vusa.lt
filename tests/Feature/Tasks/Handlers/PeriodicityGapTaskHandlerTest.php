<?php

use App\Actions\GetInstitutionRepresentatives;
use App\Events\MeetingFullyCreated;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('PeriodicityGapTaskHandler', function () {
    test('creates periodicity gap task for institution', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create student rep type and duty
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        // Create user assigned to duty (active now)
        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $handler = app(PeriodicityGapTaskHandler::class);

        $representatives = GetInstitutionRepresentatives::execute($institution);

        $task = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(7),
        );

        expect($task)->not->toBeNull()
            ->and($task->action_type)->toBe(ActionType::PeriodicityGap)
            ->and($task->taskable_type)->toBe(Institution::class)
            ->and($task->taskable_id)->toBe($institution->id)
            ->and($task->users)->toHaveCount(1)
            ->and($task->users->first()->id)->toBe($user->id)
            ->and($task->completed_at)->toBeNull();
    });

    test('does not create duplicate periodicity gap task', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create student rep type and duty
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $handler = app(PeriodicityGapTaskHandler::class);
        $representatives = GetInstitutionRepresentatives::execute($institution);

        // Create first task
        $task1 = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(7),
        );

        // Try to create second task
        $task2 = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(3),
        );

        // Should return the same task
        expect($task2->id)->toBe($task1->id);

        // Should only have one task
        $taskCount = Task::query()
            ->where('taskable_type', Institution::class)
            ->where('taskable_id', $institution->id)
            ->where('action_type', ActionType::PeriodicityGap)
            ->count();

        expect($taskCount)->toBe(1);
    });

    test('completes periodicity gap task when meeting is created', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create student rep type and duty
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $handler = app(PeriodicityGapTaskHandler::class);
        $representatives = GetInstitutionRepresentatives::execute($institution);

        // Create periodicity gap task
        $task = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(7),
        );

        expect($task->completed_at)->toBeNull();

        // Create meeting for this institution
        $meeting = Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => now()->addWeek()]);

        // Dispatch the event (simulating what the controller does)
        event(new MeetingFullyCreated($meeting));

        $task->refresh();

        expect($task->completed_at)->not->toBeNull();
    });

    test('completes periodicity gap task when check-in is created', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create student rep type and duty
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $handler = app(PeriodicityGapTaskHandler::class);
        $representatives = GetInstitutionRepresentatives::execute($institution);

        // Create periodicity gap task
        $task = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(7),
        );

        expect($task->completed_at)->toBeNull();

        // Create check-in for this institution
        InstitutionCheckIn::factory()
            ->for($institution)
            ->for($user)
            ->for($tenant)
            ->create([
                'start_date' => now(),
                'end_date' => now()->addMonth(),
            ]);

        $task->refresh();

        expect($task->completed_at)->not->toBeNull();
    });
});

describe('GetInstitutionRepresentatives', function () {
    test('returns current representatives for institution', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create student rep type
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        // Create active user
        $activeUser = User::factory()->create();
        $activeUser->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        // Create expired user
        $expiredUser = User::factory()->create();
        $expiredUser->duties()->attach($duty, [
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        $representatives = GetInstitutionRepresentatives::execute($institution);

        expect($representatives)->toHaveCount(1)
            ->and($representatives->first()->id)->toBe($activeUser->id);
    });

    test('returns empty collection when no student rep type', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        // Create duty without student rep type
        $duty = Duty::factory()
            ->for($institution)
            ->create();

        $user = User::factory()->create();
        $user->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $representatives = GetInstitutionRepresentatives::execute($institution);

        expect($representatives)->toBeEmpty();
    });
});

describe('RepopulateTasks command for institutions', function () {
    test('command runs without errors', function () {
        $this->artisan('tasks:repopulate institution --dry-run')
            ->assertExitCode(0);
    });
});
