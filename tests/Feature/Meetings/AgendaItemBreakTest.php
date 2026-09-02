<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Services\MeetingCompletionService;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();

    $externalType = Type::factory()->forInstitutions(InstitutionScope::University)->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->institution->types()->attach($externalType);

    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach($this->institution);

    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
});

test('break is an accepted agenda item type', function (): void {
    $agendaItem = AgendaItem::factory()->for($this->meeting)->create(['order' => 1]);

    asUser($this->admin)
        ->patch(route('agendaItems.update', $agendaItem->id), ['type' => 'break'])
        ->assertSessionHasNoErrors();

    expect($agendaItem->fresh()->type)->toBe(AgendaItemType::Break);
});

test('a break needs no vote to count the meeting complete', function (): void {
    AgendaItem::factory()->for($this->meeting)->break()->create(['order' => 1]);

    // A break records no outcome, so demanding one would leave the meeting permanently
    // incomplete — the same reasoning informational and deferred items already rely on.
    expect(app(MeetingCompletionService::class)->calculate($this->meeting->fresh()))
        ->toBe('complete');
});

test('a break is counted as a completed agenda item', function (): void {
    AgendaItem::factory()->for($this->meeting)->break()->create(['order' => 1]);
    AgendaItem::factory()->for($this->meeting)->informational()->create(['order' => 2]);

    $counts = app(AgendaCompletionTaskHandler::class)->getAgendaItemTypeCounts($this->meeting->fresh());

    expect($counts['break'])->toBe(1)
        ->and($counts['informational'])->toBe(1)
        ->and($counts['voting'])->toBe(0)
        ->and($counts['unset'])->toBe(0);
});

test('the index does not list a break-only meeting as incomplete', function (): void {
    AgendaItem::factory()->for($this->meeting)->break()->create(['order' => 1]);

    $incomplete = Meeting::factory()->create();
    $incomplete->institutions()->attach($this->institution);
    AgendaItem::factory()->for($incomplete)->voting()->create(['order' => 1]);

    // The index filter is built in SQL, so it holds its own notion of "needs a vote" and
    // has to agree with the enum.
    asUser($this->admin)
        ->get(route('meetings.index', ['filters' => json_encode(['completion_status' => ['incomplete']])]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('data', 1)
            ->where('data.0.id', $incomplete->id));
});

test('the vote-free types are exactly the non-voting ones', function (): void {
    expect(AgendaItemType::voteFreeValues())
        ->toBe(['informational', 'deferred', 'break'])
        ->and(AgendaItemType::Voting->requiresVote())->toBeTrue()
        ->and(AgendaItemType::Break->requiresVote())->toBeFalse();
});

test('a break carries a localized label', function (): void {
    expect(AgendaItemType::Break->label('lt'))->toBe('Pertrauka')
        ->and(AgendaItemType::Break->label('en'))->toBe('Break');
});
