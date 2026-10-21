<?php

use App\Enums\AgendaItemType;
use App\Enums\InstitutionScope;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Services\MeetingCompletionService;
use App\Tasks\Handlers\AgendaCompletionTaskHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();

    $externalType = InstitutionType::factory()->withGovernanceScope(InstitutionScope::University)->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->institution->types()->attach($externalType);

    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach($this->institution);

    $this->admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
});

test('withdrawn is an accepted agenda item type', function (): void {
    $agendaItem = AgendaItem::factory()->for($this->meeting)->create(['order' => 1]);

    asUser($this->admin)
        ->patch(route('agendaItems.update', $agendaItem->id), ['type' => 'withdrawn'])
        ->assertSessionHasNoErrors();

    expect($agendaItem->fresh()->type)->toBe(AgendaItemType::Withdrawn);
});

test('an unknown agenda item type is rejected', function (): void {
    $agendaItem = AgendaItem::factory()->for($this->meeting)->create(['order' => 1]);

    asUser($this->admin)
        ->patch(route('agendaItems.update', $agendaItem->id), ['type' => 'cancelled'])
        ->assertSessionHasErrors('type');
});

test('a withdrawn item needs no vote to count the meeting complete', function (): void {
    AgendaItem::factory()->for($this->meeting)->withdrawn()->create(['order' => 1]);

    expect(app(MeetingCompletionService::class)->calculate($this->meeting->fresh()))
        ->toBe('complete');
});

test('withdrawn items are counted on their own', function (): void {
    AgendaItem::factory()->for($this->meeting)->withdrawn()->create(['order' => 1]);
    AgendaItem::factory()->for($this->meeting)->deferred()->create(['order' => 2]);

    $counts = app(AgendaCompletionTaskHandler::class)->getAgendaItemTypeCounts($this->meeting->fresh());

    expect($counts['withdrawn'])->toBe(1)
        ->and($counts['deferred'])->toBe(1)
        ->and($counts['unset'])->toBe(0);
});
