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

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

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

describe('agenda items controller', function (): void {
    test('admin can create agenda items', function (): void {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Test Item 1', 'Test Item 2'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount + 2)
            ->and($this->meeting->agendaItems()->count())->toEqual(2);

        $items = $this->meeting->agendaItems()->pluck('title')->toArray();
        expect($items)->toContain('Test Item 1')
            ->toContain('Test Item 2');
    });

    test('cannot create agenda items with empty titles', function (): void {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['', '  '],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['agendaItemTitles.0', 'agendaItemTitles.1']);

        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount);
    });

    test('agenda items require a meeting id', function (): void {
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'agendaItemTitles' => ['Test Item'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['meeting_id']);

        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount);
    });

    test('agenda items creation does not trigger task creation', function (): void {
        $initialTaskCount = $this->meeting->tasks()->count();

        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['TBD Item'],
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Verify agenda item was created
        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount + 1);

        // Verify no tasks were created regardless of agenda item content
        expect($this->meeting->fresh()->tasks()->count())->toEqual($initialTaskCount);
    });

    test('can update agenda item details with votes', function (): void {
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
        expect($agendaItem->title)->toEqual('Updated Title')
            ->and($agendaItem->description)->toEqual('New Description');

        // Check that the vote was created
        $vote = $agendaItem->votes()->first();
        expect($vote)->not->toBeNull()
            ->and($vote->is_main)->toBeTrue()
            ->and($vote->decision)->toEqual('positive')
            ->and($vote->student_vote)->toEqual('neutral')
            ->and($vote->student_benefit)->toEqual('negative');
    });

    test('cannot update a vote owned by another agenda item', function (): void {
        $agendaItem = $this->meeting->agendaItems()->create([
            'title' => ['lt' => 'First item', 'en' => 'First item'],
            'order' => 1,
        ]);
        $otherAgendaItem = $this->meeting->agendaItems()->create([
            'title' => ['lt' => 'Second item', 'en' => 'Second item'],
            'order' => 2,
        ]);
        $foreignVote = Vote::factory()->create(['agenda_item_id' => $otherAgendaItem->id]);

        asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem), [
                'votes' => [[
                    'id' => $foreignVote->id,
                    'is_main' => false,
                ]],
            ])
            ->assertForbidden();

        expect($foreignVote->fresh()->is_main)->toBeTrue();
    });

    test('validates agenda item vote values', function (): void {
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
        expect($agendaItem->title)->toEqual('Original Title')
            ->and($agendaItem->votes()->count())->toEqual(0);
    });

    test('validates agenda item vote title length', function (): void {
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Vote Title Test'],
            ]);

        $agendaItem = $this->meeting->agendaItems()->first();
        $longTitle = str_repeat('a', 201);

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'votes' => [
                    [
                        'is_main' => true,
                        'title' => $longTitle,
                        'decision' => 'positive',
                        'student_vote' => 'positive',
                        'student_benefit' => 'positive',
                    ],
                ],
            ]);

        $response->assertStatus(302);
        // A plain string is normalized onto the `lt` translation, so the length rule
        // reports there — see NormalizesTranslatableInput.
        $response->assertSessionHasErrors(['votes.0.title.lt']);

        $agendaItem->refresh();
        expect($agendaItem->votes()->count())->toEqual(0);
    });

    // Meetings are soft-deletable, so deletion has to be reversible. Agenda items are
    // not soft-deletable and votes cascade off them, so removing them here would make
    // restore return an empty meeting.
    test('deleting a meeting keeps its agenda items so it can be restored', function (): void {
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Item One', 'Item Two'],
            ]);

        expect($this->meeting->agendaItems()->count())->toEqual(2);

        $this->meeting->delete();

        expect(AgendaItem::where('meeting_id', $this->meeting->id)->count())->toEqual(2);

        $this->meeting->restore();

        expect($this->meeting->fresh()->agendaItems()->count())->toEqual(2);
    });

    test('permanently deleting a meeting also deletes its agenda items', function (): void {
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Item One', 'Item Two'],
            ]);

        $this->meeting->delete();
        $this->meeting->forceDelete();

        expect(AgendaItem::where('meeting_id', $this->meeting->id)->count())->toEqual(0);
    });

    test('can delete an agenda item', function (): void {
        // First create an agenda item
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Item to Delete'],
            ]);

        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount + 1);

        $agendaItem = $this->meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->delete(route('agendaItems.destroy', $agendaItem->id));

        // Deleted from its own record, `back()` would be a 404; the meeting is where it lived.
        $response->assertRedirect(route('meetings.show', $this->meeting->id));
        $response->assertSessionHas('success');

        expect(AgendaItem::count())->toEqual($this->initialAgendaItemCount);
    });

    test('keeps the times recognised in a pasted timetable', function (): void {
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Studijų tvarka', 'Kiti klausimai'],
                'startTimes' => ['10:00', null],
                'endTimes' => ['10:30', null],
            ])
            ->assertSessionHasNoErrors();

        $items = $this->meeting->agendaItems()->orderBy('order')->get();

        expect(substr((string) $items[0]->start_time, 0, 5))->toBe('10:00')
            ->and(substr((string) $items[0]->end_time, 0, 5))->toBe('10:30')
            ->and($items[1]->start_time)->toBeNull();
    });

    test('rejects a pasted time that is not a clock time', function (): void {
        asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $this->meeting->id,
                'agendaItemTitles' => ['Studijų tvarka'],
                'startTimes' => ['25:00'],
            ])
            ->assertSessionHasErrors('startTimes.0');
    });

    test('admin can open the agenda item record', function (): void {
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $this->meeting->id,
        ]);

        $response = asUser($this->admin)
            ->get(route('agendaItems.show', $agendaItem->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('agendaItem.id', $agendaItem->id)
            ->where('abilities.update', true)
            ->where('abilities.delete', true)
        );
    });

    /** Emails and notifications still carry the old editor URL. */
    test('the old edit URL redirects to the record, keeping the focus', function (): void {
        $agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

        asUser($this->admin)
            ->get(route('agendaItems.edit', ['agendaItem' => $agendaItem->id, 'focus' => 'votes', 'walk' => 'missing']))
            ->assertRedirect(route('agendaItems.show', ['agendaItem' => $agendaItem->id, 'focus' => 'votes']));
    });

    test('tells each sibling what it still lacks', function (): void {
        $untyped = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 1, 'type' => null]);
        $informational = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 2, 'type' => 'informational']);

        asUser($this->admin)
            ->get(route('agendaItems.show', $untyped->id))
            ->assertInertia(fn ($page) => $page
                ->where('siblingAgendaItems.0.missing.type', 'agenda_item_type_missing')
                ->where('siblingAgendaItems.1.missing', null)
            );
    });

    test('record returns ordered sibling agenda items for navigation', function (): void {
        $first = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 1, 'title' => 'First']);
        $second = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 2, 'title' => 'Second']);
        $third = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 3, 'title' => 'Third']);

        $response = asUser($this->admin)
            ->get(route('agendaItems.show', $second->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Representation/ShowAgendaItem')
            ->where('agendaItem.id', $second->id)
            ->has('siblingAgendaItems', 3)
            ->where('siblingAgendaItems.0.id', $first->id)
            ->where('siblingAgendaItems.1.id', $second->id)
            ->where('siblingAgendaItems.2.id', $third->id)
        );
    });

    /**
     * A note row exists as soon as the collaborative editor saves, so the sibling list
     * must report content, not existence — otherwise every visited item is flagged.
     */
    test('has_notes only reports siblings whose notes hold content', function (): void {
        $blank = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 1]);
        $written = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 2]);

        $blank->note()->create(['yjs_state' => base64_encode('state'), 'notes_html' => '<p></p>']);
        $written->note()->create(['yjs_state' => base64_encode('state'), 'notes_html' => '<p>Pastaba</p>']);

        asUser($this->admin)
            ->get(route('agendaItems.show', $blank->id))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->where('siblingAgendaItems.0.has_notes', false)
                ->where('siblingAgendaItems.1.has_notes', true)
            );
    });

    test('unauthorized user cannot open the agenda item record', function (): void {
        $agendaItem = AgendaItem::factory()->create([
            'meeting_id' => $this->meeting->id,
        ]);

        $outsider = makeUser(Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? $this->tenant);

        $response = asUser($outsider)
            ->get(route('agendaItems.show', $agendaItem->id));

        $response->assertStatus(403);
    });
});

describe('reorder', function (): void {
    test('admin can reorder agenda items of their own meeting', function (): void {
        $first = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 1]);
        $second = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 2]);

        asUser($this->admin)
            ->post(route('agendaItems.reorder'), [
                'meeting_id' => $this->meeting->id,
                'agenda_items' => [
                    ['id' => $first->id, 'order' => 2],
                    ['id' => $second->id, 'order' => 1],
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        expect($first->refresh()->order)->toEqual(2)
            ->and($second->refresh()->order)->toEqual(1);
    });

    test('unauthorized user cannot reorder another meeting\'s agenda items', function (): void {
        $first = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 1]);
        $second = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id, 'order' => 2]);

        $outsider = makeUser(Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? $this->tenant);

        asUser($outsider)
            ->post(route('agendaItems.reorder'), [
                'meeting_id' => $this->meeting->id,
                'agenda_items' => [
                    ['id' => $first->id, 'order' => 2],
                    ['id' => $second->id, 'order' => 1],
                ],
            ])
            ->assertStatus(403);

        expect($first->refresh()->order)->toEqual(1)
            ->and($second->refresh()->order)->toEqual(2);
    });
});
