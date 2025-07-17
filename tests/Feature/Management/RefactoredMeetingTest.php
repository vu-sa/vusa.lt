<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    // Create a basic user for authorization tests
    $this->user = makeUser($this->tenant);

    // Create an admin user with permissions
    $this->admin = User::factory()->create();

    // Create admin role with super admin permissions
    $adminRole = Role::firstOrCreate(['name' => config('permission.super_admin_role_name'), 'guard_name' => 'web']);

    // Assign admin role to user - super admin has all permissions
    $this->admin->assignRole($adminRole);

    // Create an institution for testing
    $this->institution = Institution::factory()->for($this->tenant)->create();

    // Create a meeting type using the Type model
    $this->meetingType = Type::firstOrCreate(['title' => 'Test Meeting Type']);

    // Record initial DB counts
    $this->initialMeetingCount = Meeting::count();
    $this->initialAgendaItemCount = AgendaItem::count();
});

// Authorization tests from MeetingTest.php
describe('authorization tests', function () {
    describe('regular user', function () {
        beforeEach(function () {
            asUser($this->user)->get(route('dashboard'))->assertStatus(200);
        });

        test('cannot create a meeting without permission', function () {
            $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

            asUser($this->user)
                ->post(route('meetings.store'), [
                    'start_time' => $startTime,
                    'institution_id' => $this->institution->id,
                    'type_id' => $this->meetingType->id,
                ])
                ->assertStatus(302)
                ->assertRedirectToRoute('dashboard');

            $this->assertEquals($this->initialMeetingCount, Meeting::count());
        });

        test('cannot view meetings index without permission', function () {
            asUser($this->user)
                ->get(route('meetings.index'))
                ->assertStatus(302)
                ->assertRedirectToRoute('dashboard');
        });
    });

    describe('admin user', function () {
        beforeEach(function () {
            asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
        });

        test('can access meetings index with permission', function () {
            asUser($this->admin)
                ->get(route('meetings.index'))
                ->assertStatus(200);
        });

        test('can create a meeting with permission', function () {
            $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

            $response = asUser($this->admin)
                ->post(route('meetings.store'), [
                    'start_time' => $startTime,
                    'institution_id' => $this->institution->id,
                    'type_id' => $this->meetingType->id,
                ]);

            $response->assertStatus(302);
            $response->assertSessionHas('success', 'Posėdis sukurtas sėkmingai!');

            $this->assertEquals($this->initialMeetingCount + 1, Meeting::count());

            $meeting = Meeting::latest('id')->first();
            $this->assertNotNull($meeting);
            $this->assertEquals(1, $meeting->institutions()->count());
            $this->assertEquals($this->institution->id, $meeting->institutions->first()->id);
        });

        test('cannot create a meeting with invalid data', function () {
            $priorCount = Meeting::count();

            $response = asUser($this->admin)
                ->post(route('meetings.store'), [
                    'start_time' => null,
                    'institution_id' => $this->institution->id,
                ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['start_time']);

            $this->assertEquals($priorCount, Meeting::count());
        });

        test('meeting title is automatically generated', function () {
            $futureDate = Carbon::now()->addDays(1);
            $startTime = $futureDate->format('Y-m-d H:i:s');

            asUser($this->admin)
                ->post(route('meetings.store'), [
                    'start_time' => $startTime,
                    'institution_id' => $this->institution->id,
                    'type_id' => $this->meetingType->id,
                ]);

            $meeting = Meeting::latest('id')->first();

            // Get the expected format but don't check exact equality since test locale might differ
            $this->assertStringContainsString($futureDate->format('Y'), $meeting->title);
            $this->assertStringContainsString('posėdis', $meeting->title);
        });
    });
});

describe('refactored meeting creation', function () {
    test('requires at least one agenda item', function () {
        // Create a meeting first
        $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        asUser($this->admin)
            ->post(route('meetings.store'), [
                'start_time' => $startTime,
                'institution_id' => $this->institution->id,
                'type_id' => $this->meetingType->id,
            ]);

        $meeting = Meeting::latest('id')->first();

        $initialCount = AgendaItem::count();

        // Try to submit empty agenda items
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => [],
            ]);

        $response->assertSessionHasErrors(['agendaItemTitles']);
        $this->assertEquals($initialCount, AgendaItem::count());

        // Now submit with valid agenda items
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Valid agenda item'],
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialCount + 1, AgendaItem::count());
    });

    test('cannot submit empty strings as agenda items', function () {
        // Create a meeting first
        $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        asUser($this->admin)
            ->post(route('meetings.store'), [
                'start_time' => $startTime,
                'institution_id' => $this->institution->id,
                'type_id' => $this->meetingType->id,
            ]);

        $meeting = Meeting::latest('id')->first();

        $initialCount = AgendaItem::count();

        // Try to submit with empty strings
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['', '   '],
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals($initialCount, AgendaItem::count());
    });

    test('can submit multiple agenda items at once', function () {
        // Create a meeting first
        $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        asUser($this->admin)
            ->post(route('meetings.store'), [
                'start_time' => $startTime,
                'institution_id' => $this->institution->id,
                'type_id' => $this->meetingType->id,
            ]);

        $meeting = Meeting::latest('id')->first();

        $initialCount = AgendaItem::count();

        // Submit multiple agenda items
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => [
                    'First agenda item',
                    'Second agenda item',
                    'Third agenda item',
                ],
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals($initialCount + 3, AgendaItem::count());

        $agendaItems = $meeting->agendaItems()->pluck('title')->toArray();
        $this->assertContains('First agenda item', $agendaItems);
        $this->assertContains('Second agenda item', $agendaItems);
        $this->assertContains('Third agenda item', $agendaItems);
    });

    test('placeholder tasks are no longer created for placeholder agenda items', function () {
        // Create a meeting
        $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        asUser($this->admin)
            ->post(route('meetings.store'), [
                'start_time' => $startTime,
                'institution_id' => $this->institution->id,
                'type_id' => $this->meetingType->id,
            ]);

        $meeting = Meeting::latest('id')->first();
        $initialTaskCount = $meeting->tasks()->count();

        // Test with TBD placeholder - should NOT create task
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['TBD'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify no tasks were created
        $this->assertEquals($initialTaskCount, $meeting->fresh()->tasks()->count());

        // Clear existing items
        $meeting->agendaItems()->delete();

        // Test with normal agenda items - should also NOT create task
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Real agenda item'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify still no tasks created
        $this->assertEquals($initialTaskCount, $meeting->fresh()->tasks()->count());
    });
});

describe('end-to-end refactored meeting flow', function () {
    test('creating and managing a full meeting', function () {
        $initialMeetingCount = Meeting::count();
        $initialAgendaItemCount = AgendaItem::count();

        // 1. Create a meeting
        $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

        $response = asUser($this->admin)
            ->post(route('meetings.store'), [
                'start_time' => $startTime,
                'institution_id' => $this->institution->id,
                'type_id' => $this->meetingType->id,
            ]);

        $meeting = Meeting::latest('id')->first();
        $this->assertNotNull($meeting);

        // 2. Add agenda items - make sure to clear any existing items first
        $meeting->agendaItems()->delete(); // Ensure we start with 0 items

        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Discussion item', 'Decision item', 'Information item'],
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(3, $meeting->fresh()->agendaItems()->count());

        // 3. Update an agenda item
        $agendaItem = $meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'title' => 'Updated discussion',
                'description' => 'This is an important discussion',
                'decision' => 'positive',
                'student_vote' => 'neutral',
                'student_benefit' => 'positive',
            ]);

        $response->assertSessionHas('success');

        $agendaItem->refresh();
        $this->assertEquals('Updated discussion', $agendaItem->title);
        $this->assertEquals('This is an important discussion', $agendaItem->description);
        $this->assertEquals('positive', $agendaItem->decision);

        // 4. Delete an agenda item
        $agendaItemToDelete = $meeting->agendaItems()->skip(1)->first();

        $response = asUser($this->admin)
            ->delete(route('agendaItems.destroy', $agendaItemToDelete->id));

        $response->assertSessionHas('success');
        $this->assertEquals(2, $meeting->fresh()->agendaItems()->count());

        // 5. Add more agenda items later
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Late addition item'],
            ]);

        $response->assertSessionHas('success');
        $this->assertEquals(3, $meeting->fresh()->agendaItems()->count());

        // 6. View the complete meeting
        $response = asUser($this->admin)
            ->get(route('meetings.show', $meeting->id));

        $response->assertStatus(200);

        // 7. Clean up
        $meeting->delete();
        $this->assertEquals($initialMeetingCount, Meeting::count());
        $this->assertEquals($initialAgendaItemCount, AgendaItem::count());
    });
});
