<?php

use App\Enums\MeetingType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
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

    // Create a meeting for testing
    $startTime = Carbon::now()->addDays(1);
    $this->meeting = Meeting::create([
        'title' => $startTime->locale('lt-LT')->isoFormat('YYYY MMMM DD [d.] HH.mm [val.]').' posėdis',
        'start_time' => $startTime->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    // Create an agenda item for testing
    $this->agendaItem = AgendaItem::create([
        'meeting_id' => $this->meeting->id,
        'title' => 'Test Agenda Item',
        'order' => 1,
        'type' => 'voting',
    ]);
});

describe('vote controller', function () {
    test('admin can create a vote for an agenda item', function () {
        $response = asUser($this->admin)
            ->post(route('votes.store'), [
                'agenda_item_id' => $this->agendaItem->id,
                'is_main' => true,
                'title' => 'Main Vote',
                'decision' => 'positive',
                'student_vote' => 'positive',
                'student_benefit' => 'positive',
            ]);

        $response->assertStatus(302);
        $this->assertEquals(1, $this->agendaItem->votes()->count());

        $vote = $this->agendaItem->votes()->first();
        expect($vote->is_main)->toBeTrue();
        expect($vote->title)->toBe('Main Vote');
        expect($vote->decision)->toBe('positive');
        expect($vote->student_vote)->toBe('positive');
    });

    test('admin can update a vote', function () {
        $vote = Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'decision' => 'positive',
        ]);

        $response = asUser($this->admin)
            ->patch(route('votes.update', $vote), [
                'decision' => 'negative',
                'student_vote' => 'negative',
            ]);

        $response->assertStatus(302);

        $vote->refresh();
        expect($vote->decision)->toBe('negative');
        expect($vote->student_vote)->toBe('negative');
    });

    test('admin can delete a vote', function () {
        $vote = Vote::factory()->for($this->agendaItem, 'agendaItem')->create();

        $response = asUser($this->admin)
            ->delete(route('votes.destroy', $vote));

        $response->assertStatus(302);
        expect(Vote::find($vote->id))->toBeNull();
    });

    test('admin can set a vote as main', function () {
        $mainVote = Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create();
        $additionalVote = Vote::factory()->additional()->for($this->agendaItem, 'agendaItem')->create();

        $response = asUser($this->admin)
            ->post(route('votes.setMain', $additionalVote));

        $response->assertStatus(302);

        $mainVote->refresh();
        $additionalVote->refresh();

        expect($mainVote->is_main)->toBeFalse();
        expect($additionalVote->is_main)->toBeTrue();
    });

    test('agenda item can have multiple votes', function () {
        Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'title' => 'Main Vote',
            'decision' => 'positive',
        ]);

        Vote::factory()->additional()->for($this->agendaItem, 'agendaItem')->create([
            'title' => 'Additional Vote 1',
            'decision' => 'negative',
        ]);

        Vote::factory()->additional()->for($this->agendaItem, 'agendaItem')->create([
            'title' => 'Additional Vote 2',
            'decision' => 'neutral',
        ]);

        expect($this->agendaItem->votes()->count())->toBe(3);
        expect($this->agendaItem->mainVote()->first()->title)->toBe('Main Vote');
        expect($this->agendaItem->additionalVotes()->count())->toBe(2);
    });

    test('updating agenda item syncs votes', function () {
        // Create initial votes
        $existingVote = Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'title' => 'Original Vote',
        ]);

        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $this->agendaItem), [
                'title' => 'Updated Agenda Item',
                'votes' => [
                    [
                        'id' => $existingVote->id,
                        'is_main' => true,
                        'title' => 'Updated Vote Title',
                        'decision' => 'positive',
                        'student_vote' => 'positive',
                        'order' => 0,
                    ],
                    [
                        'id' => null, // New vote
                        'is_main' => false,
                        'title' => 'New Additional Vote',
                        'decision' => 'negative',
                        'student_vote' => 'negative',
                        'order' => 1,
                    ],
                ],
            ]);

        $response->assertStatus(302);

        $this->agendaItem->refresh();
        expect($this->agendaItem->title)->toBe('Updated Agenda Item');
        expect($this->agendaItem->votes()->count())->toBe(2);

        $existingVote->refresh();
        expect($existingVote->title)->toBe('Updated Vote Title');
    });

    test('vote alignment is tracked correctly on agenda items with multiple votes', function () {
        // Create votes with different alignments
        Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'decision' => 'positive',
            'student_vote' => 'positive', // aligned
        ]);

        // Create another agenda item with misaligned votes
        $agendaItem2 = AgendaItem::create([
            'meeting_id' => $this->meeting->id,
            'title' => 'Test Agenda Item 2',
            'order' => 2,
            'type' => 'voting',
        ]);

        Vote::factory()->main()->for($agendaItem2, 'agendaItem')->create([
            'decision' => 'positive',
            'student_vote' => 'negative', // misaligned
        ]);

        $this->meeting->refresh();

        // Load agenda items with votes
        $this->meeting->load('agendaItems.votes');

        // Get all votes from the meeting
        $allVotes = $this->meeting->agendaItems->flatMap(fn ($item) => $item->votes);

        // Count aligned and misaligned votes
        $votesWithBoth = $allVotes->filter(fn ($v) => ! empty($v->student_vote) && ! empty($v->decision));
        $alignedCount = $votesWithBoth->filter(fn ($v) => $v->student_vote === $v->decision)->count();
        $misalignedCount = $votesWithBoth->count() - $alignedCount;

        expect($alignedCount)->toBe(1);
        expect($misalignedCount)->toBe(1);
        expect($allVotes->count())->toBe(2);
    });

    test('updating agenda item syncs is_consensus field on votes', function () {
        // Create initial vote without consensus
        $existingVote = Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'title' => 'Original Vote',
            'is_consensus' => false,
        ]);

        // Update with consensus enabled
        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $this->agendaItem), [
                'title' => 'Updated Agenda Item',
                'votes' => [
                    [
                        'id' => $existingVote->id,
                        'is_main' => true,
                        'is_consensus' => true,
                        'title' => 'Consensus Vote',
                        'decision' => 'positive',
                        'student_vote' => 'positive',
                        'student_benefit' => 'positive',
                        'order' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(302);

        $existingVote->refresh();
        expect($existingVote->is_consensus)->toBeTrue();
        expect($existingVote->decision)->toBe('positive');
        expect($existingVote->student_vote)->toBe('positive');
        expect($existingVote->student_benefit)->toBe('positive');
    });

    test('creating new vote via agenda item update sets is_consensus correctly', function () {
        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $this->agendaItem), [
                'title' => 'Test Agenda Item',
                'votes' => [
                    [
                        'id' => null, // New vote
                        'is_main' => true,
                        'is_consensus' => true,
                        'title' => 'New Consensus Vote',
                        'decision' => 'positive',
                        'student_vote' => 'positive',
                        'student_benefit' => 'positive',
                        'order' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(302);

        $this->agendaItem->refresh();
        $vote = $this->agendaItem->votes()->first();

        expect($vote)->not->toBeNull();
        expect($vote->is_consensus)->toBeTrue();
        expect($vote->is_main)->toBeTrue();
    });

    test('is_consensus can be toggled off via agenda item update', function () {
        // Create vote with consensus enabled
        $vote = Vote::factory()->main()->for($this->agendaItem, 'agendaItem')->create([
            'is_consensus' => true,
            'decision' => 'positive',
            'student_vote' => 'positive',
            'student_benefit' => 'positive',
        ]);

        // Update with consensus disabled
        $response = asUser($this->admin)
            ->patch(route('agendaItems.update', $this->agendaItem), [
                'votes' => [
                    [
                        'id' => $vote->id,
                        'is_main' => true,
                        'is_consensus' => false,
                        'decision' => null,
                        'student_vote' => null,
                        'student_benefit' => null,
                        'order' => 0,
                    ],
                ],
            ]);

        $response->assertStatus(302);

        $vote->refresh();
        expect($vote->is_consensus)->toBeFalse();
        expect($vote->decision)->toBeNull();
    });

    test('vote factory supports consensus state', function () {
        $consensusVote = Vote::factory()
            ->consensus()
            ->for($this->agendaItem, 'agendaItem')
            ->create();

        expect($consensusVote->is_consensus)->toBeTrue();
        expect($consensusVote->decision)->toBe('positive');
        expect($consensusVote->student_vote)->toBe('positive');
        expect($consensusVote->student_benefit)->toBe('positive');
    });
});

describe('vote authorization', function () {
    test('unauthenticated users cannot create votes', function () {
        $response = $this->post(route('votes.store'), [
            'agenda_item_id' => $this->agendaItem->id,
            'is_main' => true,
        ]);

        $response->assertRedirect(route('login'));
    });

    test('users without permission cannot create votes', function () {
        // Create a user without any special permissions
        $regularUser = makeUser($this->tenant);

        $response = asUser($regularUser)
            ->post(route('votes.store'), [
                'agenda_item_id' => $this->agendaItem->id,
                'is_main' => true,
            ]);

        $response->assertStatus(403);
    });
});
