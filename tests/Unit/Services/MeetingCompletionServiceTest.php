<?php

use App\Enums\AgendaItemType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;
use App\Services\MeetingCompletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new MeetingCompletionService;
});

describe('MeetingCompletionService', function () {
    describe('calculate', function () {
        test('returns no_items when meeting has no agenda items', function () {
            $meeting = Meeting::factory()->create();

            expect($this->service->calculate($meeting))->toBe('no_items');
        });

        test('returns incomplete when agenda item has no votes', function () {
            $meeting = Meeting::factory()->create();
            AgendaItem::factory()->create(['meeting_id' => $meeting->id]);

            expect($this->service->calculate($meeting))->toBe('incomplete');
        });

        test('returns complete when all voting items have complete main votes', function () {
            $meeting = Meeting::factory()->create();
            $item = AgendaItem::factory()->create(['meeting_id' => $meeting->id, 'type' => AgendaItemType::Voting]);
            Vote::factory()->create([
                'agenda_item_id' => $item->id,
                'is_main' => true,
                'student_vote' => 'positive',
                'decision' => 'positive',
                'student_benefit' => 'yes',
            ]);

            expect($this->service->calculate($meeting))->toBe('complete');
        });

        test('returns incomplete when main vote is missing fields', function () {
            $meeting = Meeting::factory()->create();
            $item = AgendaItem::factory()->create(['meeting_id' => $meeting->id]);
            Vote::factory()->create([
                'agenda_item_id' => $item->id,
                'is_main' => true,
                'student_vote' => 'positive',
                'decision' => null,
                'student_benefit' => null,
            ]);

            expect($this->service->calculate($meeting))->toBe('incomplete');
        });

        test('returns complete when any vote is complete if no main vote exists', function () {
            $meeting = Meeting::factory()->create();
            $item = AgendaItem::factory()->create(['meeting_id' => $meeting->id]);
            Vote::factory()->create([
                'agenda_item_id' => $item->id,
                'is_main' => false,
                'student_vote' => 'positive',
                'decision' => 'positive',
                'student_benefit' => 'yes',
            ]);

            expect($this->service->calculate($meeting))->toBe('complete');
        });

        test('ignores informational items for completion', function () {
            $meeting = Meeting::factory()->create();
            AgendaItem::factory()->create([
                'meeting_id' => $meeting->id,
                'type' => AgendaItemType::Informational,
            ]);

            expect($this->service->calculate($meeting))->toBe('complete');
        });

        test('returns incomplete when mix of complete and incomplete items', function () {
            $meeting = Meeting::factory()->create();

            $completeItem = AgendaItem::factory()->create([
                'meeting_id' => $meeting->id,
                'type' => AgendaItemType::Voting,
            ]);
            Vote::factory()->create([
                'agenda_item_id' => $completeItem->id,
                'is_main' => true,
                'student_vote' => 'positive',
                'decision' => 'positive',
                'student_benefit' => 'yes',
            ]);

            $incompleteItem = AgendaItem::factory()->create([
                'meeting_id' => $meeting->id,
                'type' => AgendaItemType::Voting,
            ]);
            Vote::factory()->create([
                'agenda_item_id' => $incompleteItem->id,
                'is_main' => true,
                'student_vote' => null,
                'decision' => null,
                'student_benefit' => null,
            ]);

            expect($this->service->calculate($meeting))->toBe('incomplete');
        });
    });
});
