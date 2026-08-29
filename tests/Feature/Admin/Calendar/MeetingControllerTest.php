<?php

use App\Enums\InstitutionScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Relationshipable;
use App\Models\Relationship;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Support\MorphMap;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    // Create a basic user for authorization tests
    $this->user = makeUser($this->tenant);

    // Create an admin user with Communication Coordinator role
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    // Create an institution for testing
    $this->institution = Institution::factory()->for($this->tenant)->create();

    // Create a meeting type using the Type model
    $this->meetingType = Type::firstOrCreate(['title' => 'Test Meeting Type']);

    // Record initial DB counts
    $this->initialMeetingCount = Meeting::count();
    $this->initialAgendaItemCount = AgendaItem::count();
});

// Authorization tests from MeetingTest.php
describe('authorization tests', function (): void {
    describe('regular user', function (): void {
        beforeEach(function (): void {
            asUser($this->user)->get(route('dashboard'))->assertStatus(200);
        });

        test('cannot create a meeting without permission', function (): void {
            $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

            asUser($this->user)
                ->post(route('meetings.store'), [
                    'start_time' => $startTime,
                    'institution_id' => $this->institution->id,
                    'type_id' => $this->meetingType->id,
                ])
                ->assertStatus(403);

            expect(Meeting::count())->toEqual($this->initialMeetingCount);
        });

        test('cannot view meetings index without permission', function (): void {
            asUser($this->user)
                ->get(route('meetings.index'))
                ->assertStatus(403);
        });
    });

    describe('admin user', function (): void {
        beforeEach(function (): void {
            asUser($this->admin)->get(route('dashboard'))->assertStatus(200);
        });

        test('can access meetings index with permission', function (): void {
            asUser($this->admin)
                ->get(route('meetings.index'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component('Admin/Representation/IndexMeeting'));
        });

        test('can create a meeting with permission', function (): void {
            $startTime = Carbon::now()->addDays(1)->format('Y-m-d H:i:s');

            $response = asUser($this->admin)
                ->post(route('meetings.store'), [
                    'start_time' => $startTime,
                    'institution_id' => $this->institution->id,
                    'type_id' => $this->meetingType->id,
                ]);

            $response->assertStatus(302);
            $response->assertSessionHas('success', 'Posėdis sukurtas sėkmingai!');

            expect(Meeting::count())->toEqual($this->initialMeetingCount + 1);

            $meeting = Meeting::latest('id')->first();
            expect($meeting)->not->toBeNull()
                ->and($meeting->institutions()->count())->toEqual(1)
                ->and($meeting->institutions->first()->id)->toEqual($this->institution->id);
        });

        test('cannot create a meeting with invalid data', function (): void {
            $priorCount = Meeting::count();

            $response = asUser($this->admin)
                ->post(route('meetings.store'), [
                    'start_time' => null,
                    'institution_id' => $this->institution->id,
                ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['start_time']);

            expect(Meeting::count())->toEqual($priorCount);
        });

        test('meeting title is automatically generated', function (): void {
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
            expect($meeting->title)->toContain($futureDate->format('Y'));
            expect($meeting->title)->toContain('posėdis');
        });
    });
});

describe('refactored meeting creation', function (): void {
    test('requires at least one agenda item', function (): void {
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
        expect(AgendaItem::count())->toEqual($initialCount);

        // Now submit with valid agenda items
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Valid agenda item'],
            ]);

        $response->assertSessionHas('success');
        expect(AgendaItem::count())->toEqual($initialCount + 1);
    });

    test('cannot submit empty strings as agenda items', function (): void {
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
        expect(AgendaItem::count())->toEqual($initialCount);
    });

    test('can submit multiple agenda items at once', function (): void {
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
        expect(AgendaItem::count())->toEqual($initialCount + 3);

        $agendaItems = $meeting->agendaItems()->pluck('title')->toArray();
        expect($agendaItems)->toContain('First agenda item')
            ->toContain('Second agenda item')
            ->toContain('Third agenda item');
    });

    test('placeholder tasks are no longer created for placeholder agenda items', function (): void {
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
        expect($meeting->fresh()->tasks()->count())->toEqual($initialTaskCount);

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
        expect($meeting->fresh()->tasks()->count())->toEqual($initialTaskCount);
    });
});

describe('end-to-end refactored meeting flow', function (): void {
    test('creating and managing a full meeting', function (): void {
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
        expect($meeting)->not->toBeNull();

        // 2. Add agenda items - make sure to clear any existing items first
        $meeting->agendaItems()->delete(); // Ensure we start with 0 items

        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Discussion item', 'Decision item', 'Information item'],
            ]);

        $response->assertSessionHas('success');
        expect($meeting->fresh()->agendaItems()->count())->toEqual(3);

        // 3. Update an agenda item with votes
        $agendaItem = $meeting->agendaItems()->first();

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $agendaItem->id), [
                'title' => 'Updated discussion',
                'description' => 'This is an important discussion',
                'type' => 'voting',
                'votes' => [
                    [
                        'is_main' => true,
                        'decision' => 'positive',
                        'student_vote' => 'neutral',
                        'student_benefit' => 'positive',
                    ],
                ],
            ]);

        $response->assertSessionHas('success');

        $agendaItem->refresh();
        expect($agendaItem->title)->toEqual('Updated discussion')
            ->and($agendaItem->description)->toEqual('This is an important discussion');

        // Check the vote was created
        $vote = $agendaItem->votes()->first();
        expect($vote)->not->toBeNull()
            ->and($vote->decision)->toEqual('positive');

        // 4. Delete an agenda item
        $agendaItemToDelete = $meeting->agendaItems()->skip(1)->first();

        $response = asUser($this->admin)
            ->delete(route('agendaItems.destroy', $agendaItemToDelete->id));

        $response->assertSessionHas('success');
        expect($meeting->fresh()->agendaItems()->count())->toEqual(2);

        // 5. Add more agenda items later
        $response = asUser($this->admin)
            ->post(route('agendaItems.store'), [
                'meeting_id' => $meeting->id,
                'agendaItemTitles' => ['Late addition item'],
            ]);

        $response->assertSessionHas('success');
        expect($meeting->fresh()->agendaItems()->count())->toEqual(3);

        // 6. View the complete meeting
        $response = asUser($this->admin)
            ->get(route('meetings.show', $meeting->id));

        $response->assertStatus(200);

        // 7. Clean up. Deleting is reversible, so the agenda survives until the meeting
        // is permanently deleted.
        $meeting->delete();
        expect(Meeting::count())->toEqual($initialMeetingCount)
            ->and(AgendaItem::count())->toEqual($initialAgendaItemCount + 3);

        $meeting->forceDelete();
        expect(AgendaItem::count())->toEqual($initialAgendaItemCount);
    });
});

describe('joint meeting institution management', function (): void {
    beforeEach(function (): void {
        $this->meeting = Meeting::factory()->create(['start_time' => Carbon::now()->addDays(1)]);
        $this->meeting->institutions()->attach($this->institution->id);
        $this->secondInstitution = Institution::factory()->for($this->tenant)->create();
    });

    test('is_joint returns false for single institution', function (): void {
        expect($this->meeting->is_joint)->toBeFalse();
    });

    test('is_joint returns true for multiple institutions', function (): void {
        $this->meeting->institutions()->attach($this->secondInstitution->id);
        $this->meeting->unsetRelation('institutions');

        expect($this->meeting->is_joint)->toBeTrue();
    });

    test('admin can attach an additional institution', function (): void {
        asUser($this->admin)
            ->post(route('meetings.institutions.attach', $this->meeting), [
                'institution_id' => $this->secondInstitution->id,
            ])
            ->assertStatus(302)
            ->assertSessionHas('success');

        expect($this->meeting->fresh()->institutions()->count())->toBe(2);
    });

    test('cannot attach an already-attached institution', function (): void {
        asUser($this->admin)
            ->post(route('meetings.institutions.attach', $this->meeting), [
                'institution_id' => $this->institution->id,
            ])
            ->assertSessionHasErrors(['institution_id']);

        expect($this->meeting->fresh()->institutions()->count())->toBe(1);
    });

    test('unauthorized user cannot attach institution', function (): void {
        asUser($this->user)
            ->post(route('meetings.institutions.attach', $this->meeting), [
                'institution_id' => $this->secondInstitution->id,
            ])
            ->assertStatus(403);

        expect($this->meeting->fresh()->institutions()->count())->toBe(1);
    });

    test('admin can detach an institution when multiple exist', function (): void {
        $this->meeting->institutions()->attach($this->secondInstitution->id);

        asUser($this->admin)
            ->delete(route('meetings.institutions.detach', [$this->meeting, $this->secondInstitution]))
            ->assertStatus(302)
            ->assertSessionHas('success');

        expect($this->meeting->fresh()->institutions()->count())->toBe(1);
    });

    test('cannot detach the last institution', function (): void {
        asUser($this->admin)
            ->delete(route('meetings.institutions.detach', [$this->meeting, $this->institution]))
            ->assertStatus(302)
            ->assertSessionHas('error');

        expect($this->meeting->fresh()->institutions()->count())->toBe(1);
    });
});

describe('relationship-based meeting access', function (): void {
    test('user can view meeting via authorized institution relationship', function (): void {
        // Create two institutions
        $sourceInstitution = Institution::factory()->for($this->tenant)->create();
        $targetInstitution = Institution::factory()->for($this->tenant)->create();

        // Create a relationship between them
        $relationship = Relationship::create([
            'name' => 'Test Advisory Relationship',
            'slug' => 'test-advisory-'.uniqid(),
        ]);
        Relationshipable::create([
            'relationship_id' => $relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $sourceInstitution->id,
            'related_model_id' => $targetInstitution->id,
            'scope' => 'within-tenant',
            'bidirectional' => false, // Outgoing only authorization
        ]);

        // Create a meeting for the target institution
        $meeting = Meeting::factory()->create([
            'start_time' => Carbon::now()->addDays(1),
        ]);
        $meeting->institutions()->attach($targetInstitution->id);

        // Create a user with a duty at the source institution
        $user = makeUser($this->tenant);
        $duty = Duty::factory()->for($sourceInstitution)->create();
        $duty->users()->attach($user->id, [
            'start_date' => Carbon::now()->subMonth(),
        ]);
        // Clear relationship cache
        Cache::forget("related_institutions_{$sourceInstitution->id}");

        // User should be able to view the meeting via authorized relationship
        $response = asUser($user)->get(route('meetings.show', $meeting->id));
        $response->assertStatus(200);
    });

    test('user cannot view meeting via non-bidirectional incoming relationship', function (): void {
        // Create two institutions
        $sourceInstitution = Institution::factory()->for($this->tenant)->create();
        $targetInstitution = Institution::factory()->for($this->tenant)->create();

        // Create a relationship where source -> target (source is authorized to see target's meetings)
        // But NOT bidirectional, so target is NOT authorized to see source's meetings
        $relationship = Relationship::create([
            'name' => 'Test Advisory Relationship',
            'slug' => 'test-advisory-'.uniqid(),
        ]);
        Relationshipable::create([
            'relationship_id' => $relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $sourceInstitution->id,
            'related_model_id' => $targetInstitution->id,
            'scope' => 'within-tenant',
            'bidirectional' => false, // NOT bidirectional
        ]);

        // Create a meeting for the SOURCE institution
        $meeting = Meeting::factory()->create([
            'start_time' => Carbon::now()->addDays(1),
        ]);
        $meeting->institutions()->attach($sourceInstitution->id);

        // Create a user with a duty at the TARGET institution (incoming relationship side)
        $user = makeUser($this->tenant);
        $duty = Duty::factory()->for($targetInstitution)->create();
        $duty->users()->attach($user->id, [
            'start_date' => Carbon::now()->subMonth(),
        ]);
        // Clear relationship cache
        Cache::forget("related_institutions_{$targetInstitution->id}");

        // User should NOT be able to view the meeting (incoming relationship is not authorized)
        $response = asUser($user)->get(route('meetings.show', $meeting->id));
        $response->assertStatus(403);
    });

    test('user can view meeting via bidirectional incoming relationship', function (): void {
        // Create two institutions
        $sourceInstitution = Institution::factory()->for($this->tenant)->create();
        $targetInstitution = Institution::factory()->for($this->tenant)->create();

        // Create a bidirectional relationship
        $relationship = Relationship::create([
            'name' => 'Test Advisory Relationship',
            'slug' => 'test-advisory-'.uniqid(),
        ]);
        Relationshipable::create([
            'relationship_id' => $relationship->id,
            'relationshipable_type' => MorphMap::alias(Institution::class),
            'relationshipable_id' => $sourceInstitution->id,
            'related_model_id' => $targetInstitution->id,
            'scope' => 'within-tenant',
            'bidirectional' => true, // BIDIRECTIONAL - both sides authorized
        ]);

        // Create a meeting for the SOURCE institution
        $meeting = Meeting::factory()->create([
            'start_time' => Carbon::now()->addDays(1),
        ]);
        $meeting->institutions()->attach($sourceInstitution->id);

        // Create a user with a duty at the TARGET institution (incoming relationship side)
        $user = makeUser($this->tenant);
        $duty = Duty::factory()->for($targetInstitution)->create();
        $duty->users()->attach($user->id, [
            'start_date' => Carbon::now()->subMonth(),
        ]);
        // Clear relationship cache
        Cache::forget("related_institutions_{$targetInstitution->id}");

        // User SHOULD be able to view the meeting (bidirectional = authorized)
        $response = asUser($user)->get(route('meetings.show', $meeting->id));
        $response->assertStatus(200);
    });
});

describe('meeting search indexing', function (): void {
    test('searchable array exposes representative user names', function (): void {
        $meeting = Meeting::factory()->create([
            'start_time' => Carbon::now()->addDays(1),
        ]);
        $meeting->institutions()->attach($this->institution->id);

        $duty = Duty::factory()->for($this->institution)->create([
            'name' => ['lt' => 'Pirmininkas', 'en' => 'Chair'],
        ]);
        $member = User::factory()->create(['name' => 'Jonas Jonaitis']);
        $duty->users()->attach($member->id, ['start_date' => now()->subYear(), 'end_date' => null]);

        $searchable = $meeting->fresh()->toSearchableArray();

        expect($searchable)->toHaveKey('user_names')
            ->and($searchable['user_names'])->toContain('Jonas Jonaitis');
    });
});

describe('cross-tenant parent scoping', function (): void {
    /**
     * The meetings.create permission is tenant-agnostic (HasCommonChecks::create), so the
     * institution the meeting is filed under has to be scoped separately — otherwise a
     * coordinator for one padalinys can create meetings inside another one's institution.
     */
    test('cannot create a meeting for an institution outside the user\'s tenant scope', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
        $foreignInstitution = Institution::factory()->for($otherTenant)->create();

        asUser($this->admin)->post(route('meetings.store'), [
            'title' => 'Svetimas posėdis',
            'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
            'institution_id' => $foreignInstitution->id,
        ])->assertSessionHasErrors('institution_id');

        expect(Meeting::count())->toEqual($this->initialMeetingCount);
    });

    /**
     * The window hides the checkbox for an external body; this is the half of that rule the
     * client cannot enforce.
     */
    test('cannot announce a meeting of a body VU SA only delegates into', function (): void {
        $external = Institution::factory()->for($this->tenant)->create();
        $external->types()->attach(Type::factory()->forInstitutions(InstitutionScope::University)->create());

        asUser($this->admin)->post(route('meetings.store'), [
            'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
            'institution_id' => $external->id,
            'announce_in_calendar' => true,
        ])->assertSessionHasErrors('announce_in_calendar');

        expect(Meeting::count())->toEqual($this->initialMeetingCount);
    });

    test('announces a VU SA body\'s meeting as a draft event', function (): void {
        $internal = Institution::factory()->for($this->tenant)->create();
        $internal->types()->attach(Type::factory()->forInstitutions(InstitutionScope::Vusa)->create());

        asUser($this->admin)->post(route('meetings.store'), [
            'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
            'institution_id' => $internal->id,
            'announce_in_calendar' => true,
        ])->assertRedirect();

        expect(Meeting::latest('id')->first()->calendarEvent?->is_draft)->toBeTrue();
    });

    test('cannot add agenda items to a meeting the user cannot update', function (): void {
        $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
        $foreignInstitution = Institution::factory()->for($otherTenant)->create();

        $foreignMeeting = Meeting::factory()->create(['start_time' => Carbon::now()->addDay()]);
        $foreignMeeting->institutions()->attach($foreignInstitution->id);

        asUser($this->admin)->post(route('agendaItems.store'), [
            'meeting_id' => $foreignMeeting->id,
            'agendaItemTitles' => ['Svetimas klausimas'],
        ])->assertStatus(403);

        expect($foreignMeeting->agendaItems()->count())->toEqual(0);
    });
});
