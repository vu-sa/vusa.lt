<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\Client;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();

    $this->admin = User::factory()->create();
    $this->admin->assignRole(config('permission.super_admin_role_name'));
});

function indexedDocument(Institution|Meeting|AgendaItem $model): array
{
    return (new Client(config('scout.typesense.client-settings')))
        ->collections[$model->searchableAs()]->documents[(string) $model->id]->retrieve();
}

function meetingWithAgendaItem(): array
{
    $institution = Institution::factory()->create();
    $institution->types()->attach(InstitutionType::factory()->withGovernanceScope(InstitutionScope::University)->create());
    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach($institution);

    return [$meeting, AgendaItem::factory()->for($meeting)->create(['type' => AgendaItemType::Voting])];
}

test('a new member shows in the institution list straight away', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create();

    Dutiable::factory()->forDuty($duty)->forUser(User::factory()->create(['name' => 'Fresh Member']))->create([
        'start_date' => now()->subDay(),
        'end_date' => null,
    ]);

    $document = indexedDocument($institution);

    expect($document['current_user_names'])->toContain('Fresh Member')
        ->and($document['members_total'])->toBe(1);
});

test('saving a vote refreshes the meeting and agenda item documents', function (): void {
    [$meeting, $item] = meetingWithAgendaItem();

    $this->actingAs($this->admin)->post(route('votes.store'), [
        'agenda_item_id' => $item->id,
        'is_main' => true,
        'student_vote' => 'positive',
        'decision' => 'positive',
        'student_benefit' => 'positive',
    ])->assertSessionHasNoErrors();

    expect(indexedDocument($meeting))
        ->outcome_aligned->toBe(1)
        ->completion_status->toBe('complete')
        ->vote_alignment_status->toBe('all_match')
        ->and(indexedDocument($item))
        ->decision->toBe('positive')
        ->main_vote_exists->toBeTrue();
});

test('the main vote of a new vote counts, not the one it replaced', function (): void {
    [$meeting, $item] = meetingWithAgendaItem();
    Vote::factory()->for($item, 'agendaItem')->create(['is_main' => true, 'student_vote' => 'positive', 'decision' => 'positive', 'student_benefit' => 'positive']);

    $this->actingAs($this->admin)->post(route('votes.store'), [
        'agenda_item_id' => $item->id,
        'is_main' => true,
        'student_vote' => 'positive',
        'decision' => 'negative',
        'student_benefit' => 'negative',
    ])->assertSessionHasNoErrors();

    expect(indexedDocument($meeting))
        ->outcome_aligned->toBe(0)
        ->outcome_misaligned->toBe(1);
});

test('deleting a vote takes it out of the meeting outcome', function (): void {
    [$meeting, $item] = meetingWithAgendaItem();
    $vote = Vote::factory()->for($item, 'agendaItem')->create(['is_main' => true, 'student_vote' => 'positive', 'decision' => 'positive', 'student_benefit' => 'positive']);

    $this->actingAs($this->admin)->delete(route('votes.destroy', $vote))->assertSessionHasNoErrors();

    expect(indexedDocument($meeting))
        ->outcome_aligned->toBe(0)
        ->completion_status->toBe('incomplete');
});

test('adding or removing an agenda item updates the meeting count', function (): void {
    [$meeting, $item] = meetingWithAgendaItem();
    expect(indexedDocument($meeting)['agenda_items_count'])->toBe(1);

    $second = AgendaItem::factory()->for($meeting)->create();
    expect(indexedDocument($meeting)['agenda_items_count'])->toBe(2);

    $second->delete();
    expect(indexedDocument($meeting)['agenda_items_count'])->toBe(1);
});

test('a private agenda item stays out of the indexed outcome', function (): void {
    [$meeting, $item] = meetingWithAgendaItem();
    $item->update(['is_private' => true]);

    Vote::factory()->for($item, 'agendaItem')->create(['is_main' => true, 'student_vote' => 'positive', 'decision' => 'positive', 'student_benefit' => 'positive']);

    expect(indexedDocument($meeting)['outcome_aligned'] ?? 0)->toBe(0);
});
