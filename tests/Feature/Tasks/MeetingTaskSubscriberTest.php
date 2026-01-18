<?php

use App\Actions\GetInstitutionFollowersToNotify;
use App\Actions\GetMeetingAdministrators;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Notifications\MeetingAgendaCompletedNotification;
use App\Notifications\MeetingCreatedNotification;
use App\Settings\AtstovavimasSettings;
use App\Tasks\Enums\ActionType;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use App\Tasks\Handlers\AgendaCreationTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Tasks\MeetingTaskTestHelpers;

uses(RefreshDatabase::class, MeetingTaskTestHelpers::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});

describe('MeetingTaskSubscriber', function () {
    describe('agenda creation task', function () {
        test('creates agenda creation task when meeting is created with student reps', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $institution = Institution::factory()
                ->for($tenant)
                ->create();

            // Create student rep type
            $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
                ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

            // Create a duty with student rep type
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

            // Create meeting for this institution (no agenda items yet)
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create([
                    'start_time' => now(),
                ]);

            // Dispatch the event (simulating what the controller does after full setup)
            event(new \App\Events\MeetingFullyCreated($meeting));

            // Check that creation task was created
            $creationTask = Task::query()
                ->where('taskable_type', Meeting::class)
                ->where('taskable_id', $meeting->id)
                ->where('action_type', ActionType::AgendaCreation)
                ->first();

            expect($creationTask)->not->toBeNull()
                ->and($creationTask->users)->toHaveCount(1)
                ->and($creationTask->users->first()->id)->toBe($user->id)
                ->and($creationTask->completed_at)->toBeNull();
        });

        test('completes creation task when first agenda item is added', function () {
            [$meeting, $creationTask] = $this->createMeetingWithCreationTask();

            expect($creationTask->completed_at)->toBeNull();

            // Add first agenda item
            AgendaItem::factory()
                ->incomplete()
                ->create(['meeting_id' => $meeting->id, 'order' => 1]);

            $creationTask->refresh();

            expect($creationTask->completed_at)->not->toBeNull();
        });

        test('creates completion task when first agenda item is added', function () {
            [$meeting, $creationTask] = $this->createMeetingWithCreationTask();

            // Add first agenda item
            AgendaItem::factory()
                ->incomplete()
                ->create(['meeting_id' => $meeting->id, 'order' => 1]);

            // Check that completion task was created
            $completionTask = Task::query()
                ->where('taskable_type', Meeting::class)
                ->where('taskable_id', $meeting->id)
                ->where('action_type', ActionType::AgendaCompletion)
                ->first();

            expect($completionTask)->not->toBeNull()
                ->and($completionTask->metadata['items_total'])->toBe(1)
                ->and($completionTask->metadata['items_completed'])->toBe(0);
        });

        test('does not create any task when no student reps exist', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $institution = Institution::factory()
                ->for($tenant)
                ->create();

            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            $tasks = Task::query()
                ->where('taskable_type', Meeting::class)
                ->where('taskable_id', $meeting->id)
                ->get();

            expect($tasks)->toBeEmpty();
        });
    });

    describe('agenda completion task progress', function () {
        test('updates progress when agenda item is completed', function () {
            // Set up meeting with completion task
            [$meeting, $completionTask] = $this->createMeetingWithCompletionTask();

            expect($completionTask->metadata['items_completed'])->toBe(0);

            // Complete one agenda item
            $agendaItem = $meeting->agendaItems->first();
            $agendaItem->update([
                'student_vote' => 'už',
                'decision' => 'Approved',
                'student_benefit' => 'Great benefit',
            ]);

            $completionTask->refresh();

            expect($completionTask->metadata['items_completed'])->toBe(1)
                ->and($completionTask->completed_at)->toBeNull();
        });

        test('auto-completes task when all agenda items are completed', function () {
            [$meeting, $completionTask] = $this->createMeetingWithCompletionTask(agendaItemCount: 2);

            // Complete all agenda items
            foreach ($meeting->agendaItems as $agendaItem) {
                $agendaItem->update([
                    'student_vote' => 'už',
                    'decision' => 'Approved',
                    'student_benefit' => 'Benefits students',
                ]);
            }

            $completionTask->refresh();

            expect($completionTask->completed_at)->not->toBeNull()
                ->and($completionTask->metadata['items_completed'])->toBe(2);
        });

        test('updates total items when agenda item is added', function () {
            [$meeting, $completionTask] = $this->createMeetingWithCompletionTask(agendaItemCount: 2);

            expect($completionTask->metadata['items_total'])->toBe(2);

            // Add another agenda item
            AgendaItem::factory()
                ->incomplete()
                ->create([
                    'meeting_id' => $meeting->id,
                    'order' => 3,
                ]);

            $completionTask->refresh();

            expect($completionTask->metadata['items_total'])->toBe(3);
        });

        test('updates total items when agenda item is deleted', function () {
            [$meeting, $completionTask] = $this->createMeetingWithCompletionTask(agendaItemCount: 3);

            expect($completionTask->metadata['items_total'])->toBe(3);

            // Delete one agenda item
            $meeting->agendaItems->first()->delete();

            $completionTask->refresh();

            expect($completionTask->metadata['items_total'])->toBe(2);
        });
    });

    describe('notifications', function () {
        test('notifies administrators when meeting is created', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            // Set up global admin role
            $globalRole = Role::factory()->create(['guard_name' => 'web']);
            $settings = app(AtstovavimasSettings::class);
            $settings->setGlobalVisibilityRoleIds([$globalRole->id]);
            $settings->save();

            // Create admin with global visibility role
            $institution = Institution::factory()->for($tenant)->create();
            $duty = Duty::factory()->for($institution)->create();
            $duty->roles()->attach($globalRole);

            $admin = User::factory()->create();
            $admin->duties()->attach($duty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // Dispatch the event (simulating what the controller does after full setup)
            event(new \App\Events\MeetingFullyCreated($meeting));

            Notification::assertSentTo($admin, MeetingCreatedNotification::class);
        });

        test('notifies administrators when all agenda items are completed', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            // Set up tenant visibility role
            $tenantRole = Role::factory()->create(['guard_name' => 'web']);
            $settings = app(AtstovavimasSettings::class);
            $settings->setTenantVisibilityRoleIds([$tenantRole->id]);
            $settings->save();

            // Create coordinator with tenant visibility role
            $institution = Institution::factory()->for($tenant)->create();
            $coordinatorDuty = Duty::factory()->for($institution)->create();
            $coordinatorDuty->roles()->attach($tenantRole);

            $coordinator = User::factory()->create();
            $coordinator->duties()->attach($coordinatorDuty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create student rep type and duty
            $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
                ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

            $repDuty = Duty::factory()
                ->for($institution)
                ->hasAttached($studentRepType, [], 'types')
                ->create();

            $rep = User::factory()->create();
            $rep->duties()->attach($repDuty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create meeting with agenda items
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            $agendaItem = AgendaItem::factory()
                ->incomplete()
                ->create(['meeting_id' => $meeting->id, 'order' => 1]);

            // Clear previous notifications (from meeting creation)
            Notification::fake();

            // Complete the agenda item
            $agendaItem->update([
                'student_vote' => 'už',
                'decision' => 'Passed',
                'student_benefit' => 'Students benefit',
            ]);

            Notification::assertSentTo($coordinator, MeetingAgendaCompletedNotification::class);
        });

        test('does not send duplicate notifications to same user with multiple roles', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            // Set up both global and tenant visibility roles
            $globalRole = Role::factory()->create(['guard_name' => 'web']);
            $tenantRole = Role::factory()->create(['guard_name' => 'web']);

            $settings = app(AtstovavimasSettings::class);
            $settings->setGlobalVisibilityRoleIds([$globalRole->id]);
            $settings->setTenantVisibilityRoleIds([$tenantRole->id]);
            $settings->save();

            // Create user with both roles
            $institution = Institution::factory()->for($tenant)->create();
            $duty = Duty::factory()->for($institution)->create();
            $duty->roles()->attach([$globalRole->id, $tenantRole->id]);

            $admin = User::factory()->create();
            $admin->duties()->attach($duty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // Dispatch the event (simulating what the controller does after full setup)
            event(new \App\Events\MeetingFullyCreated($meeting));

            // User should only receive one notification despite having multiple qualifying roles
            Notification::assertSentToTimes($admin, MeetingCreatedNotification::class, 1);
        });

        test('notifies followers when meeting is created', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution = Institution::factory()->for($tenant)->create();

            // Create a follower who is not an admin
            $follower = User::factory()->create();
            $follower->followedInstitutions()->attach($institution);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // Dispatch the event
            event(new \App\Events\MeetingFullyCreated($meeting));

            Notification::assertSentTo($follower, MeetingCreatedNotification::class);
        });

        test('does not notify followers who have muted the institution', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution = Institution::factory()->for($tenant)->create();

            // Create a follower who has muted the institution
            $mutedFollower = User::factory()->create();
            $mutedFollower->followedInstitutions()->attach($institution);
            $mutedFollower->mutedInstitutions()->attach($institution, ['muted_at' => now()]);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // Dispatch the event
            event(new \App\Events\MeetingFullyCreated($meeting));

            Notification::assertNotSentTo($mutedFollower, MeetingCreatedNotification::class);
        });

        test('follower only receives one notification even if they follow multiple meeting institutions', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution1 = Institution::factory()->for($tenant)->create();
            $institution2 = Institution::factory()->for($tenant)->create();

            // Create a follower who follows both institutions
            $follower = User::factory()->create();
            $follower->followedInstitutions()->attach([$institution1->id, $institution2->id]);

            // Create meeting attached to both institutions
            $meeting = Meeting::factory()
                ->hasAttached([$institution1, $institution2])
                ->create(['start_time' => now()]);

            // Dispatch the event
            event(new \App\Events\MeetingFullyCreated($meeting));

            Notification::assertSentToTimes($follower, MeetingCreatedNotification::class, 1);
        });

        test('user who is both institution manager and follower only receives one notification', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution = Institution::factory()->for($tenant)->create();

            // Create the institution-manager-role type for GetInstitutionManagers
            $institutionManagerType = Type::query()->where('slug', 'institution-manager-role')->first()
                ?? Type::factory()->create(['slug' => 'institution-manager-role', 'model_type' => Role::class]);

            // Create a role attached to the institution manager type
            $managerRole = Role::factory()->create(['guard_name' => 'web']);
            $managerRole->types()->attach($institutionManagerType);

            // Create duty with the manager role
            $duty = Duty::factory()->for($institution)->create();
            $duty->roles()->attach($managerRole);

            $manager = User::factory()->create();
            $manager->duties()->attach($duty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Manager also follows the institution
            $manager->followedInstitutions()->attach($institution);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // Dispatch the event
            event(new \App\Events\MeetingFullyCreated($meeting));

            Notification::assertSentToTimes($manager, MeetingCreatedNotification::class, 1);
        });
    });

    describe('GetInstitutionFollowersToNotify', function () {
        test('returns followers who have not muted the institution', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution = Institution::factory()->for($tenant)->create();

            // Create a follower
            $follower = User::factory()->create();
            $follower->followedInstitutions()->attach($institution);

            // Create a muted follower
            $mutedFollower = User::factory()->create();
            $mutedFollower->followedInstitutions()->attach($institution);
            $mutedFollower->mutedInstitutions()->attach($institution, ['muted_at' => now()]);

            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            $followers = GetInstitutionFollowersToNotify::execute($meeting);

            expect($followers)->toHaveCount(1)
                ->and($followers->first()->id)->toBe($follower->id);
        });

        test('returns unique followers across multiple institutions', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution1 = Institution::factory()->for($tenant)->create();
            $institution2 = Institution::factory()->for($tenant)->create();

            // Create a user who follows both institutions
            $follower = User::factory()->create();
            $follower->followedInstitutions()->attach([$institution1->id, $institution2->id]);

            $meeting = Meeting::factory()
                ->hasAttached([$institution1, $institution2])
                ->create(['start_time' => now()]);

            $followers = GetInstitutionFollowersToNotify::execute($meeting);

            // Should only return the follower once (deduplicated)
            expect($followers)->toHaveCount(1)
                ->and($followers->first()->id)->toBe($follower->id);
        });
    });

    describe('GetMeetingAdministrators', function () {
        test('returns unique administrators from all sources', function () {
            $tenant = Tenant::query()->where('type', '!=', 'pkp')->first()
                ?? Tenant::factory()->create(['type' => 'padalinys']);

            $institution = Institution::factory()->for($tenant)->create();

            // Set up roles
            $globalRole = Role::factory()->create(['guard_name' => 'web']);
            $tenantRole = Role::factory()->create(['guard_name' => 'web']);

            $settings = app(AtstovavimasSettings::class);
            $settings->setGlobalVisibilityRoleIds([$globalRole->id]);
            $settings->setTenantVisibilityRoleIds([$tenantRole->id]);
            $settings->save();

            // Create global admin
            $globalDuty = Duty::factory()->for($institution)->create();
            $globalDuty->roles()->attach($globalRole);
            $globalAdmin = User::factory()->create();
            $globalAdmin->duties()->attach($globalDuty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create tenant coordinator
            $tenantDuty = Duty::factory()->for($institution)->create();
            $tenantDuty->roles()->attach($tenantRole);
            $tenantCoordinator = User::factory()->create();
            $tenantCoordinator->duties()->attach($tenantDuty, [
                'start_date' => now()->subMonth(),
                'end_date' => null,
            ]);

            // Create meeting
            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            $administrators = GetMeetingAdministrators::execute($meeting);

            expect($administrators)->toHaveCount(2)
                ->and($administrators->pluck('id'))->toContain($globalAdmin->id, $tenantCoordinator->id);
        });
    });

    describe('AgendaCompletionTaskHandler', function () {
        test('findOrCreate returns existing task if one exists', function () {
            [$meeting, $completionTask] = $this->createMeetingWithCompletionTask();

            $handler = app(AgendaCompletionTaskHandler::class);
            $foundTask = $handler->findExistingTask($meeting);

            expect($foundTask)->not->toBeNull()
                ->and($foundTask->id)->toBe($completionTask->id);
        });

        test('handles meeting with no agenda items gracefully', function () {
            $tenant = Tenant::query()->inRandomOrder()->first()
                ?? Tenant::factory()->create();

            $institution = Institution::factory()->for($tenant)->create();

            $meeting = Meeting::factory()
                ->hasAttached($institution)
                ->create(['start_time' => now()]);

            // No student reps = no tasks at all
            $tasks = Task::query()
                ->where('taskable_type', Meeting::class)
                ->where('taskable_id', $meeting->id)
                ->get();

            expect($tasks)->toBeEmpty();
        });
    });

    describe('AgendaCreationTaskHandler', function () {
        test('findExistingTask returns the creation task', function () {
            [$meeting, $creationTask] = $this->createMeetingWithCreationTask();

            $handler = app(AgendaCreationTaskHandler::class);
            $foundTask = $handler->findExistingTask($meeting);

            expect($foundTask)->not->toBeNull()
                ->and($foundTask->id)->toBe($creationTask->id);
        });

        test('completeForMeeting marks creation task as completed', function () {
            [$meeting, $creationTask] = $this->createMeetingWithCreationTask();

            expect($creationTask->completed_at)->toBeNull();

            $handler = app(AgendaCreationTaskHandler::class);
            $handler->completeForMeeting($meeting);

            $creationTask->refresh();

            expect($creationTask->completed_at)->not->toBeNull();
        });
    });
});
