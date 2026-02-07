<?php

/**
 * Tests for InstitutionCheckInTaskSubscriber.
 *
 * @see \App\Tasks\Subscribers\InstitutionCheckInTaskSubscriber
 */

use App\Actions\GetInstitutionRepresentatives;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Tasks\Handlers\PeriodicityGapTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('InstitutionCheckInTaskSubscriber', function () {
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

        // Create a periodicity gap task
        $handler = app(PeriodicityGapTaskHandler::class);
        $representatives = GetInstitutionRepresentatives::execute($institution);

        $task = $handler->findOrCreate(
            institution: $institution,
            users: $representatives,
            dueDate: now()->addDays(7),
        );

        expect($task->completed_at)->toBeNull();

        // Create a check-in for the institution (triggers subscriber)
        InstitutionCheckIn::factory()
            ->for($institution)
            ->for($user)
            ->create();

        $task->refresh();

        expect($task->completed_at)->not->toBeNull();
    });

    test('does not fail when no periodicity gap task exists', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()
            ->for($tenant)
            ->create(['is_active' => true]);

        $user = User::factory()->create();

        // Create check-in without any existing task (should not throw)
        $checkIn = InstitutionCheckIn::factory()
            ->for($institution)
            ->for($user)
            ->create();

        expect($checkIn)->not->toBeNull();
    });

    test('only completes tasks for the specific institution', function () {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution1 = Institution::factory()->for($tenant)->create(['is_active' => true]);
        $institution2 = Institution::factory()->for($tenant)->create(['is_active' => true]);

        // Create student rep type and duties for both institutions
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        foreach ([$institution1, $institution2] as $institution) {
            $duty = Duty::factory()
                ->for($institution)
                ->hasAttached($studentRepType, [], 'types')
                ->create();

            $user = User::factory()->create();
            $user->duties()->attach($duty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);
        }

        $handler = app(PeriodicityGapTaskHandler::class);

        // Create tasks for both institutions
        $task1 = $handler->findOrCreate(
            institution: $institution1,
            users: GetInstitutionRepresentatives::execute($institution1),
            dueDate: now()->addDays(7),
        );

        $task2 = $handler->findOrCreate(
            institution: $institution2,
            users: GetInstitutionRepresentatives::execute($institution2),
            dueDate: now()->addDays(7),
        );

        expect($task1->completed_at)->toBeNull();
        expect($task2->completed_at)->toBeNull();

        // Create check-in only for institution1
        $user = $institution1->duties()->first()->current_users()->first();

        InstitutionCheckIn::factory()
            ->for($institution1)
            ->for($user)
            ->create();

        $task1->refresh();
        $task2->refresh();

        expect($task1->completed_at)->not->toBeNull()
            ->and($task2->completed_at)->toBeNull();
    });
});
