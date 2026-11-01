<?php

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Vote;
use App\Services\VoteStatisticsCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

pest()->use(RefreshDatabase::class);

function meetingForOutcome(): Meeting
{
    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach(Institution::factory()->for(Tenant::query()->first())->create());

    return $meeting;
}

function outcomeItem(Meeting $meeting, AgendaItemType $type, array $votes = [], bool $private = false): AgendaItem
{
    $item = AgendaItem::factory()->for($meeting)->create(['type' => $type, 'is_private' => $private]);

    foreach ($votes as $vote) {
        Vote::factory()->create(['agenda_item_id' => $item->id, ...$vote]);
    }

    return $item;
}

test('counts each item once by its outcome vote and its type', function (): void {
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => 'positive', 'decision' => 'positive', 'is_main' => true]]);
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => 'positive', 'decision' => 'negative', 'is_main' => true]]);
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => null, 'decision' => null, 'student_benefit' => null, 'is_main' => true]]);
    outcomeItem($meeting, AgendaItemType::Informational);
    outcomeItem($meeting, AgendaItemType::Deferred);
    outcomeItem($meeting, AgendaItemType::Break);

    expect($meeting->outcomeSummary())->toMatchArray([
        'outcome_aligned' => 1,
        'outcome_misaligned' => 1,
        'outcome_unknown' => 1,
        'outcome_discussed' => 1,
        'outcome_deferred' => 1,
        'vote_alignment_status' => 'mixed',
    ]);
});

test('an additional vote does not outweigh the main vote', function (): void {
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [
        ['student_vote' => 'positive', 'decision' => 'positive', 'is_main' => true],
        ['student_vote' => 'positive', 'decision' => 'negative', 'is_main' => false],
    ]);

    expect($meeting->outcomeSummary())->toMatchArray(['outcome_aligned' => 1, 'outcome_misaligned' => 0, 'vote_alignment_status' => 'all_match']);
});

test('a consensus counts as matching the students', function (): void {
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => null, 'decision' => 'positive', 'student_benefit' => null, 'is_consensus' => true, 'is_main' => true]]);

    expect($meeting->outcomeSummary())->toMatchArray(['outcome_aligned' => 1, 'outcome_unknown' => 0, 'vote_alignment_status' => 'all_match']);
});

test('search documents carry the same counts as the summary', function (): void {
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [
        ['student_vote' => 'positive', 'decision' => 'positive', 'is_main' => true],
        ['student_vote' => 'positive', 'decision' => 'negative', 'is_main' => false],
    ]);

    expect($meeting->fresh()->toSearchableArray())->toMatchArray(['vote_matches' => 1, 'vote_mismatches' => 0, 'vote_alignment_status' => 'all_match']);
});

test('without a student perspective it counts decisions and claims no alignment', function (): void {
    $meeting = meetingForOutcome();
    $item = outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => null, 'decision' => 'negative', 'student_benefit' => null, 'is_main' => true]]);

    $summary = app(VoteStatisticsCalculator::class)->outcomeSummary(collect([$item->load('votes')]), requiresStudentPerspective: false);

    expect($summary)->toMatchArray(['outcome_negative' => 1, 'outcome_aligned' => 0, 'outcome_unknown' => 0, 'vote_alignment_status' => 'neutral']);
});

test('the indexed summary leaves private items out and withholds completion', function (): void {
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => 'positive', 'decision' => 'negative', 'is_main' => true]], private: true);
    outcomeItem($meeting, AgendaItemType::Informational);

    $document = $meeting->fresh()->toSearchableArray();

    expect($document)->toMatchArray(['outcome_misaligned' => 0, 'outcome_discussed' => 1, 'completion_status' => null]);
});

test('a viewer who may read a private item gets it counted', function (): void {
    $tenant = Tenant::query()->first();
    $meeting = meetingForOutcome();
    outcomeItem($meeting, AgendaItemType::Voting, [['student_vote' => 'positive', 'decision' => 'negative', 'is_main' => true]], private: true);

    expect($meeting->fresh()->outcomeSummary(makeAdminUser($tenant)))->toMatchArray(['outcome_misaligned' => 1]);
});

test('the institution preview gives outcomes only for meetings the viewer may open', function (): void {
    $tenant = Tenant::query()->first();
    $meeting = meetingForOutcome();
    $institution = $meeting->institutions()->first();
    outcomeItem($meeting, AgendaItemType::Informational);
    $coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);

    asUser($coordinator)->getJson(route('api.v1.admin.institutions.preview', $institution))
        ->assertOk()
        ->assertJsonPath('data.last_meetings.0.outcome.outcome_discussed', 1);

    Gate::before(fn ($user, string $ability, array $arguments) => $ability === 'view' && ($arguments[0] ?? null) instanceof Meeting ? false : null);

    asUser($coordinator)->getJson(route('api.v1.admin.institutions.preview', $institution))
        ->assertOk()
        ->assertJsonPath('data.last_meetings.0.outcome', null);
});
