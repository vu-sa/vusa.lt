<?php

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);

    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());

    $this->agendaItem = AgendaItem::factory()->for($meeting)->create([
        'type' => AgendaItemType::Voting->value,
    ]);
});

function submitVotes(AgendaItem $item, array $votes)
{
    return asUser(test()->admin)->patch(route('agendaItems.update', $item), [
        'title' => $item->title,
        'type' => $item->type?->value,
        'votes' => $votes,
    ]);
}

test('the main vote can be removed, leaving the item with none', function (): void {
    Vote::factory()->for($this->agendaItem, 'agendaItem')->create(['is_main' => true, 'decision' => 'positive']);

    submitVotes($this->agendaItem, [])->assertSessionHasNoErrors();

    expect($this->agendaItem->fresh()->votes)->toBeEmpty();
});

test('removing the main vote leaves a remaining one as main', function (): void {
    $main = Vote::factory()->for($this->agendaItem, 'agendaItem')->create(['is_main' => true, 'order' => 0]);
    $other = Vote::factory()->for($this->agendaItem, 'agendaItem')->create(['is_main' => false, 'order' => 1]);

    // The editor drops the main row and promotes the survivor, exactly as the form does.
    submitVotes($this->agendaItem, [
        ['id' => $other->id, 'is_main' => true, 'decision' => 'positive', 'order' => 0],
    ])->assertSessionHasNoErrors();

    $votes = $this->agendaItem->fresh()->votes;

    expect($votes)->toHaveCount(1)
        ->and($votes->first()->id)->toBe($other->id)
        ->and($votes->first()->is_main)->toBeTrue()
        ->and(Vote::find($main->id))->toBeNull();
});

test('a payload that forgets to promote still leaves exactly one main vote', function (): void {
    $main = Vote::factory()->for($this->agendaItem, 'agendaItem')->create(['is_main' => true, 'order' => 0]);
    $other = Vote::factory()->for($this->agendaItem, 'agendaItem')->create(['is_main' => false, 'order' => 1]);

    submitVotes($this->agendaItem, [
        ['id' => $other->id, 'is_main' => false, 'decision' => 'positive', 'order' => 0],
    ])->assertSessionHasNoErrors();

    $votes = $this->agendaItem->fresh()->votes;

    expect($votes)->toHaveCount(1)
        ->and($votes->first()->is_main)->toBeTrue()
        ->and(Vote::find($main->id))->toBeNull();
});
