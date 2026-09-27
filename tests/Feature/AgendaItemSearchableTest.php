<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('the list filter counts an item complete by the same rule as the meeting and its task', function (): void {
    $vusaType = Type::factory()->forInstitutions(InstitutionScope::Vusa)->create();
    $vuType = Type::factory()->forInstitutions(InstitutionScope::University)->create();

    $meetingOf = function (Type $type): Meeting {
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
