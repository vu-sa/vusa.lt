<?php

namespace Tests\Feature\Tasks;

use App\Events\MeetingFullyCreated;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Tasks\Enums\ActionType;

/**
 * Trait providing common meeting task test utilities.
 */
trait MeetingTaskTestHelpers
{
    /**
     * Create a meeting with student reps but no agenda items (creation task only).
     *
     * @return array{0: Meeting, 1: Task|null}
     */
    protected function createMeetingWithCreationTask(): array
    {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()->for($tenant)->create();

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

        // Create meeting (no agenda items)
        $meeting = Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => now()]);

        // Dispatch the event to trigger task creation (simulating controller behavior)
        event(new MeetingFullyCreated($meeting));

        $task = Task::query()
            ->where('taskable_type', Meeting::class)
            ->where('taskable_id', $meeting->id)
            ->where('action_type', ActionType::AgendaCreation)
            ->first();

        return [$meeting, $task];
    }

    /**
     * Create a meeting with student reps and agenda items (completion task).
     *
     * @return array{0: Meeting, 1: Task|null}
     */
    protected function createMeetingWithCompletionTask(int $agendaItemCount = 3): array
    {
        $tenant = Tenant::query()->inRandomOrder()->first()
            ?? Tenant::factory()->create();

        $institution = Institution::factory()->for($tenant)->create();

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

        // Create meeting
        $meeting = Meeting::factory()
            ->hasAttached($institution)
            ->create(['start_time' => now()]);

        // Create agenda items first (before dispatching event)
        AgendaItem::factory()
            ->count($agendaItemCount)
            ->incomplete()
            ->sequentialOrder()
            ->create(['meeting_id' => $meeting->id]);

        $meeting->refresh();
        $meeting->load('agendaItems');

        // Dispatch the event to trigger task creation (simulating controller behavior)
        event(new MeetingFullyCreated($meeting));

        $task = Task::query()
            ->where('taskable_type', Meeting::class)
            ->where('taskable_id', $meeting->id)
            ->where('action_type', ActionType::AgendaCompletion)
            ->first();

        return [$meeting, $task];
    }

    /**
     * @deprecated Use createMeetingWithCompletionTask instead
     */
    protected function createMeetingWithTask(int $agendaItemCount = 3): array
    {
        return $this->createMeetingWithCompletionTask($agendaItemCount);
    }
}
