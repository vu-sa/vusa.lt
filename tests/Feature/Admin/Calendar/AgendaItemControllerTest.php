<?php

use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();

    // Create an admin user with Communication Coordinator role
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create an institution for testing
    $this->institution = Institution::factory()->for($this->tenant)->create();

    // Create a meeting for testing agenda items
    $startTime = Carbon::now()->addDays(1);
    $this->meeting = Meeting::create([
        'title' => $startTime->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis',
        'start_time' => $startTime->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);

    $this->meeting->institutions()->attach($this->institution->id);

    // Record initial counts
    $this->initialAgendaItemCount = AgendaItem::count();
});

describe('agenda items controller', function () {
    test('admin can create agenda items', function () {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Test Item 1', 'Test Item 2'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertEquals($this->initialAgendaItemCount + 2, AgendaItem::count());
        $this->assertEquals(2, $this->meeting->agendaItems()->count());

        $items = $this->meeting->agendaItems()->pluck('title')->toArray();
        $this->assertContains('Test Item 1', $items);
        $this->assertContains('Test Item 2', $items);
    });

    test('cannot create agenda items with empty titles', function () {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['', '  '],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['agendaItemTitles.0', 'agendaItemTitles.1']);

        $this->assertEquals($this->initialAgendaItemCount, AgendaItem::count());
    });

    test('agenda items require a meeting id', function () {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'agendaItemTitles' => ['Test Item'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['meeting_id']);

        $this->assertEquals($this->initialAgendaItemCount, AgendaItem::count());
    });

    test('agenda items creation does not trigger task creation', function () {
        $initialTaskCount = $this->meeting->tasks()->count();

        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['TBD Item'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify agenda item was created
        $this->assertEquals($this->initialAgendaItemCount + 1, AgendaItem::count());

        // Verify no tasks were created regardless of agenda item content
        $this->assertEquals($initialTaskCount, $this->meeting->fresh()->tasks()->count());
    });

    test('can update agenda item details with votes', function () {
        // First create an agenda item
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Original Title'],
            ]);

        $agendaItem = $this->meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'title' => 'Updated Title',
                'description' => 'New Description',
                'type' => 'voting',
                'votes' => [
                    [
                        'is_main' => true,
                        'decision' => 'positive',
                        'student_vote' => 'neutral',
                        'student_benefit' => 'negative',
                    ],
                ],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $agendaItem->refresh();
        $this->assertEquals('Updated Title', $agendaItem->title);
        $this->assertEquals('New Description', $agendaItem->description);

        // Check that the vote was created
        $vote = $agendaItem->votes()->first();
        $this->assertNotNull($vote);
        $this->assertTrue($vote->is_main);
        $this->assertEquals('positive', $vote->decision);
        $this->assertEquals('neutral', $vote->student_vote);
        $this->assertEquals('negative', $vote->student_benefit);
    });

    test('validates agenda item vote values', function () {
        // First create an agenda item
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Original Title'],
            ]);

        $agendaItem = $this->meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'votes' => [
                    [
                        'is_main' => true,
                        'decision' => 'invalid-value', // Invalid enum value
                    ],
                ],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['votes.0.decision']);

        $agendaItem->refresh();
        $this->assertEquals('Original Title', $agendaItem->title);
        $this->assertEquals(0, $agendaItem->votes()->count());
    });

    test('deleting a meeting also deletes its agenda items', function () {
        // First create an agenda item
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Item One', 'Item Two'],
            ]);

        $this->assertEquals(2, $this->meeting->agendaItems()->count());

        // Delete the meeting
        $this->meeting->delete();

        // Verify that agenda items were deleted
        $this->assertEquals(0, AgendaItem::where('meeting_id', $this->meeting->id)->count());
    });

    test('can delete an agenda item', function () {
        // First create an agenda item
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Item to Delete'],
            ]);

        $this->assertEquals($this->initialAgendaItemCount + 1, AgendaItem::count());

        $agendaItem = $this->meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->delete(route('agendaItems.destroy', $agendaItem->id));

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $this->assertEquals($this->initialAgendaItemCount, AgendaItem::count());
    });
});
