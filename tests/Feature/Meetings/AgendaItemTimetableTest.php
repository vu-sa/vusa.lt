<?php

use App\Enums\AgendaItemType;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);

    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());

    $this->agendaItem = AgendaItem::factory()->for($meeting)->create([
        'type' => AgendaItemType::Informational->value,
    ]);
});

/**
 * MySQL normalises a TIME column to `HH:MM:SS`, SQLite stores whatever it was given. The app
 * only ever cares about the `HH:MM` head, so compare on that rather than on driver behaviour.
 */
function hhmm(?string $value): ?string
{
    return $value === null ? null : substr($value, 0, 5);
}

function updateTimes(AgendaItem $item, ?string $start, ?string $end)
{
    return asUser(test()->admin)->patch(route('agendaItems.update', $item), [
        'title' => $item->title,
        'type' => $item->type?->value,
        'start_time' => $start,
        'end_time' => $end,
    ]);
}

test('an agenda item can carry a start and an end time', function (): void {
    updateTimes($this->agendaItem, '18:30', '19:15')->assertSessionHasNoErrors();

    $fresh = $this->agendaItem->fresh();

    expect(hhmm($fresh->start_time))->toBe('18:30')
        ->and(hhmm($fresh->end_time))->toBe('19:15');
});

test('the end time must be later than the start time', function (): void {
    updateTimes($this->agendaItem, '18:30', '18:00')->assertSessionHasErrors('end_time');

    expect($this->agendaItem->fresh()->end_time)->toBeNull();
});

test('an end time is accepted on its own, and so is neither', function (): void {
    updateTimes($this->agendaItem, null, '19:15')->assertSessionHasNoErrors();
    expect(hhmm($this->agendaItem->fresh()->end_time))->toBe('19:15');

    updateTimes($this->agendaItem, null, null)->assertSessionHasNoErrors();
    expect($this->agendaItem->fresh()->end_time)->toBeNull();
});

test('times must be HH:MM', function (): void {
    updateTimes($this->agendaItem, '18:30:00', null)->assertSessionHasErrors('start_time');
});
