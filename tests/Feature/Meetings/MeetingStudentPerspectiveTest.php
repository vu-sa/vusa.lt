<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\Vote;
use App\Services\VoteStatisticsCalculator;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

/**
 * A meeting of one institution typed with the given scope, carrying one voting agenda item
 * whose main vote records only an outcome.
 */
function meetingWithDecisionOnlyVote(InstitutionScope $scope): Meeting
{
    $type = Type::factory()->forInstitutions($scope)->create();
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $institution->types()->attach($type);

    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach($institution);

    $item = AgendaItem::factory()->for($meeting)->create(['type' => AgendaItemType::Voting->value]);
    Vote::factory()->for($item, 'agendaItem')->create([
        'is_main' => true,
        'decision' => 'positive',
        'student_vote' => null,
        'student_benefit' => null,
    ]);

    return $meeting->fresh();
}

test('a VU SA meeting is complete once the outcome is recorded', function (): void {
    $meeting = meetingWithDecisionOnlyVote(InstitutionScope::Vusa);

    expect($meeting->requiresStudentPerspective())->toBeFalse()
        ->and($meeting->completion_status)->toBe('complete');
});

test('a VU meeting still needs the student vote and benefit', function (): void {
    $meeting = meetingWithDecisionOnlyVote(InstitutionScope::University);

    expect($meeting->requiresStudentPerspective())->toBeTrue()
        ->and($meeting->completion_status)->toBe('incomplete');
});

test('national and international bodies keep the student perspective', function (): void {
    expect(meetingWithDecisionOnlyVote(InstitutionScope::National)->requiresStudentPerspective())->toBeTrue()
        ->and(meetingWithDecisionOnlyVote(InstitutionScope::International)->requiresStudentPerspective())->toBeTrue();
});

test('a joint VU SA and VU meeting keeps the student perspective', function (): void {
    $meeting = meetingWithDecisionOnlyVote(InstitutionScope::Vusa);

    $vuType = Type::factory()->forInstitutions(InstitutionScope::University)->create();
    $vuInstitution = Institution::factory()->for(Tenant::query()->first())->create();
    $vuInstitution->types()->attach($vuType);
    $meeting->institutions()->attach($vuInstitution);

    expect($meeting->fresh()->requiresStudentPerspective())->toBeTrue();
});

test('vote statistics report no alignment for an internal body', function (): void {
    $meeting = meetingWithDecisionOnlyVote(InstitutionScope::Vusa);
    $votes = $meeting->load('agendaItems.votes')->agendaItems->flatMap->votes;

    $calculator = app(VoteStatisticsCalculator::class);
    $stats = $calculator->calculate($votes, requiresStudentPerspective: false);

    expect($stats['completed_votes'])->toBe(1)
        ->and($stats['all_votes_complete'])->toBeTrue()
        ->and($stats['vote_matches'])->toBe(0)
        ->and($stats['vote_mismatches'])->toBe(0)
        ->and($stats['has_any_student_vote'])->toBeFalse()
        ->and($stats['positive_outcomes'])->toBe(1)
        ->and($calculator->alignmentStatus($votes, requiresStudentPerspective: false))->toBe('neutral');
});

test('the completion filter finds VU SA meetings that only recorded an outcome', function (): void {
    $tenant = Tenant::query()->first();
    $admin = makeAdminUser($tenant);

    $complete = meetingWithDecisionOnlyVote(InstitutionScope::Vusa);
    $incomplete = meetingWithDecisionOnlyVote(InstitutionScope::University);

    // BaseIndexRequest takes `filters` as a JSON string.
    $response = asUser($admin)->get(route('meetings.index', [
        'filters' => json_encode(['completion_status' => ['complete']]),
    ]));

    $response->assertOk();

    $ids = collect($response->viewData('page')['props']['data'])->pluck('id');

    expect($ids)->toContain($complete->id)
        ->and($ids)->not->toContain($incomplete->id);
});

test('the public institution page carries the meeting scope for agenda statuses', function (): void {
    $meeting = meetingWithDecisionOnlyVote(InstitutionScope::Vusa);
    $meeting->update(['start_time' => now()->subDay()]);
    $institution = $meeting->institutions()->first();

    app(MeetingSettings::class)->fill([
        'public_meeting_institution_type_ids' => $institution->types()->pluck('types.id')->all(),
    ])->save();

    $this->get(route('contacts.institution', [
        'subdomain' => 'www',
        'lang' => 'lt',
        'institution' => $institution->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('currentYearMeetings.meetings.0.requires_student_perspective', false)
            ->where('currentYearMeetings.meetings.0.completion_status', 'complete'));
});
