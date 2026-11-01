<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

pest()->use(RefreshDatabase::class);

test('agenda item whose meeting is soft-deleted still emits the required tenant and institution fields', function (): void {
    // A soft-deleted meeting makes the `meeting` relation resolve to null. The searchable
    // array must not drop the schema-required fields, otherwise the Typesense import fails
    // with "field declared in the schema, but not found in the document".
    $meeting = Meeting::factory()->create();
    $item = AgendaItem::factory()->create(['meeting_id' => $meeting->id]);

    $meeting->delete();

    // Force the (now soft-deleted) meeting relation to re-resolve — it returns null.
    $item->unsetRelation('meeting');
    $array = $item->toSearchableArray();

    expect($array['meeting_id'])->not->toBeNull()
        ->and($array)->toHaveKey('tenant_ids')
        ->and($array['tenant_ids'])->toBe([])
        ->and($array)->toHaveKey('tenant_shortnames')
        ->and($array['tenant_shortnames'])->toBe([])
        ->and($array)->toHaveKey('institution_ids')
        ->and($array['institution_ids'])->toBe([]);
});

test('a trashed meeting takes its agenda items out of search until it is restored', function (): void {
    usesTypesense();
    config(['scout.queue' => false]);

    $meeting = Meeting::factory()->create();
    $item = AgendaItem::factory()->for($meeting)->create();
    $isIndexed = function () use ($item): bool {
        try {
            (new Client(config('scout.typesense.client-settings')))->collections[$item->searchableAs()]->documents[$item->id]->retrieve();

            return true;
        } catch (ObjectNotFound) {
            return false;
        }
    };

    expect($isIndexed())->toBeTrue();

    $meeting->delete();
    expect($isIndexed())->toBeFalse()
        ->and(AgendaItem::query()->whereKey($item->id)->exists())->toBeTrue();

    // A reindex must not bring it back either.
    AgendaItem::query()->whereKey($item->id)->searchable();
    expect($isIndexed())->toBeFalse();

    $meeting->restore();
    expect($isIndexed())->toBeTrue();
});

test('the list filter counts an item complete by the same rule as the meeting and its task', function (): void {
    $vusaType = InstitutionType::factory()->withGovernanceScope(InstitutionScope::Vusa)->create();
    $vuType = InstitutionType::factory()->withGovernanceScope(InstitutionScope::University)->create();

    $meetingOf = function (InstitutionType $type): Meeting {
        $institution = Institution::factory()->for(Tenant::query()->first())->create();
        $institution->types()->attach($type);
        $meeting = Meeting::factory()->create();
        $meeting->institutions()->attach($institution);

        return $meeting;
    };

    $vuMeeting = $meetingOf($vuType);
    $informational = AgendaItem::factory()->for($vuMeeting)->create(['type' => AgendaItemType::Informational]);
    $break = AgendaItem::factory()->for($vuMeeting)->create(['type' => AgendaItemType::Break]);
    $untyped = AgendaItem::factory()->for($vuMeeting)->create(['type' => null]);
    $decisionOnlyVu = AgendaItem::factory()->for($vuMeeting)->create(['type' => AgendaItemType::Voting]);
    Vote::factory()->for($decisionOnlyVu, 'agendaItem')->create(['is_main' => true, 'decision' => 'positive', 'student_vote' => null, 'student_benefit' => null]);

    $decisionOnlyVusa = AgendaItem::factory()->for($meetingOf($vusaType))->create(['type' => AgendaItemType::Voting]);
    Vote::factory()->for($decisionOnlyVusa, 'agendaItem')->create(['is_main' => true, 'decision' => 'positive', 'student_vote' => null, 'student_benefit' => null]);

    $isComplete = fn (AgendaItem $item): bool => $item->fresh()->toSearchableArray()['is_complete'];

    expect($isComplete($informational))->toBeTrue()
        ->and($isComplete($break))->toBeTrue()
        ->and($isComplete($untyped))->toBeFalse()
        ->and($isComplete($decisionOnlyVu))->toBeFalse()
        ->and($isComplete($decisionOnlyVusa))->toBeTrue()
        ->and($decisionOnlyVusa->fresh()->toSearchableArray()['vote_alignment_status'])->toBe('neutral');
});

test('an agenda item without a type is indexed as unset, not as a vote', function (): void {
    $meeting = Meeting::factory()->create();
    $item = AgendaItem::factory()->for($meeting)->create(['type' => null]);
    $discussed = AgendaItem::factory()->for($meeting)->create(['type' => AgendaItemType::Informational]);

    expect($item->fresh()->toSearchableArray()['type'])->toBeNull()
        ->and($discussed->fresh()->toSearchableArray()['type'])->toBe('informational');
});

test('a student position counts in either language, blank text does not', function (): void {
    $meeting = Meeting::factory()->create();
    $has = fn (array $position): bool => AgendaItem::factory()->for($meeting)->create(['student_position' => $position])->fresh()->toSearchableArray()['has_student_position'];

    expect($has(['lt' => 'Pozicija']))->toBeTrue()
        ->and($has(['en' => 'Position']))->toBeTrue()
        ->and($has(['lt' => '   ']))->toBeFalse()
        ->and($has([]))->toBeFalse();
});

test('without a main vote, the outcome fields hold what every vote shares', function (): void {
    $item = AgendaItem::factory()->for(Meeting::factory())->create(['type' => AgendaItemType::Voting]);
    Vote::factory()->for($item, 'agendaItem')->create(['is_main' => false, 'decision' => 'positive', 'student_vote' => 'positive', 'order' => 0]);
    Vote::factory()->for($item, 'agendaItem')->create(['is_main' => false, 'decision' => 'positive', 'student_vote' => 'negative', 'order' => 1]);

    $document = $item->fresh()->toSearchableArray();

    expect($document['decision'])->toBe('positive')
        ->and($document['student_vote'])->toBeNull()
        ->and($document['main_vote_exists'])->toBeFalse();
});
