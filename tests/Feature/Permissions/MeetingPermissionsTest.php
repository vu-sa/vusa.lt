<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 21)->setTime(12, 0));
    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => []])->save();
    $this->tenant = Tenant::query()->firstOrFail();
    $this->member = makeUser($this->tenant);
    $this->duty = $this->member->duties()->firstOrFail();
    $this->institution = $this->duty->institution;
});

test('automatic historical access follows inclusive duty dates', function (string $date, bool $allowed): void {
    $this->duty->pivot->update(['start_date' => '2025-03-01', 'end_date' => '2025-03-31']);
    $meeting = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => $date]);
    $item = AgendaItem::factory()->for($meeting)->create(['is_private' => true]);

    $response = asUser($this->member)->get(route('meetings.show', $meeting));

    if ($allowed) {
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('abilities.update', true)->where('abilities.createAgendaItems', true)->where('abilities.delete', false));
        expect($this->member->can('update', $item))->toBeTrue();
    } else {
        $response->assertForbidden();
        expect($this->member->can('update', $item))->toBeFalse();
    }
})->with([
    ['2025-02-28 23:59:59', false],
    ['2025-03-01 00:00:00', true],
    ['2025-03-31 23:59:59', true],
    ['2025-04-01 00:00:00', false],
]);

test('own editing adds other terms only in the granting institutions', function (): void {
    $other = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $this->member->duties()->attach($other, ['start_date' => now()->subDay()]);
    $this->duty->givePermissionTo('meetings.update.own');
    $old = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()->subYear()]);
    $otherOld = Meeting::factory()->hasAttached($other->institution)->create(['start_time' => now()->subYear()]);
    $during = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()]);

    asUser($this->member)->get(route('meetings.show', $old))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', true));
    asUser($this->member)->get(route('meetings.show', $otherOld))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', false));

    $this->duty->revokePermissionTo('meetings.update.own');
    app(ModelAuthorizer::class)->resetCache($this->member);

    asUser($this->member)->get(route('meetings.show', $old))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', false));
    asUser($this->member)->get(route('meetings.show', $during))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.update', true));
});

test('own meeting editing manages agenda items of every term only in the granting institutions', function (): void {
    $this->duty->pivot->update(['start_date' => '2026-09-01']);
    $other = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $this->member->duties()->attach($other, ['start_date' => '2026-09-01']);
    $old = AgendaItem::factory()->for(Meeting::factory()->hasAttached($this->institution)->create(['start_time' => now()->subYears(3)]))->create();
    $otherOld = AgendaItem::factory()->for(Meeting::factory()->hasAttached($other->institution)->create(['start_time' => now()->subYears(3)]))->create();

    expect($this->member->can('update', $old))->toBeFalse()
        ->and($this->member->can('delete', $old))->toBeFalse();

    $this->duty->givePermissionTo('meetings.update.own');
    app(ModelAuthorizer::class)->resetCache($this->member);

    expect($this->member->can('update', $old))->toBeTrue()
        ->and($this->member->can('delete', $old))->toBeTrue()
        ->and($this->member->can('update', $otherOld))->toBeFalse()
        ->and($this->member->can('delete', $otherOld))->toBeFalse();
});

test('a separate agenda creation grant adds items without editing the meeting or reading private content', function (): void {
    $institution = Institution::factory()->for($this->tenant)->create();
    $meeting = Meeting::factory()->hasAttached($institution)->create();
    $private = AgendaItem::factory()->for($meeting)->create(['is_private' => true, 'title' => ['lt' => 'Slaptas klausimas', 'en' => 'Private item']]);
    $this->duty->givePermissionTo(['meetings.read.padalinys', 'agendaItems.create.padalinys']);

    asUser($this->member)->get(route('meetings.show', $meeting))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('abilities.createAgendaItems', true)
            ->where('abilities.update', false)->where('abilities.reorderAgendaItems', false)
            ->where('meeting.agenda_items.0.is_redacted', true));
    asUser($this->member)->post(route('agendaItems.store'), ['meeting_id' => $meeting->id, 'agendaItemTitles' => ['Naujas klausimas']])
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($meeting->agendaItems()->count())->toBe(2)
        ->and($this->member->can('view', $private))->toBeFalse()
        ->and($this->member->can('update', $private))->toBeFalse()
        ->and($this->member->can('delete', $private))->toBeFalse();
});

test('agenda creation requires both target scope and meeting visibility', function (): void {
    $this->duty->givePermissionTo('agendaItems.create.padalinys');
    $hidden = Meeting::factory()->hasAttached(Institution::factory()->for($this->tenant))->create();
    $foreign = Meeting::factory()->hasAttached(Institution::factory()->for(Tenant::factory()))->create();

    foreach ([$hidden, $foreign] as $meeting) {
        asUser($this->member)->post(route('agendaItems.store'), ['meeting_id' => $meeting->id, 'agendaItemTitles' => ['Naujas']])
            ->assertForbidden();
    }
});

test('joint meetings accept an agenda creation grant from either institution tenant', function (): void {
    $this->duty->givePermissionTo('agendaItems.create.padalinys');
    $meeting = Meeting::factory()->hasAttached($this->institution)
        ->hasAttached(Institution::factory()->for(Tenant::factory()))->create();

    asUser($this->member)->post(route('agendaItems.store'), ['meeting_id' => $meeting->id, 'agendaItemTitles' => ['Bendras klausimas']])
        ->assertRedirect()->assertSessionHasNoErrors();
});

test('own deletion permits finding and restoring a trashed meeting without creation rights', function (): void {
    $this->duty->givePermissionTo('meetings.delete.own');
    $own = Meeting::factory()->hasAttached($this->institution)->create();
    $other = Meeting::factory()->hasAttached(Institution::factory()->for($this->tenant))->create();
    $own->delete();
    $other->delete();

    expect($this->member->can('create', Meeting::class))->toBeFalse();
    asUser($this->member)->get(route('meetings.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('deletedCount', 1));
    asUser($this->member)->getJson(route('api.v1.admin.trash.index', ['collection' => 'meetings']))
        ->assertOk()->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', (string) $own->id)
        ->assertJsonPath('data.items.0.abilities.restore', true)
        ->assertJsonPath('data.items.0.abilities.forceDelete', false);
    asUser($this->member)->patch(route('meetings.restore', $other))->assertForbidden();
    asUser($this->member)->patch(route('meetings.restore', $own))->assertRedirect();

    expect($own->fresh()->trashed())->toBeFalse();
});

test('creation rights do not allow restoring or discovering trashed meetings', function (): void {
    $this->duty->givePermissionTo('meetings.create.own');
    $meeting = Meeting::factory()->hasAttached($this->institution)->create();
    $meeting->delete();

    asUser($this->member)->getJson(route('api.v1.admin.trash.index', ['collection' => 'meetings']))
        ->assertOk()->assertJsonCount(0, 'data.items');
    asUser($this->member)->patch(route('meetings.restore', $meeting))->assertForbidden();
});

test('former members navigation and agenda suggestions do not reveal meetings outside their term', function (): void {
    $this->duty->pivot->update(['start_date' => '2025-01-01', 'end_date' => '2025-06-30']);
    $during = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => '2025-04-01']);
    $earlier = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => '2025-03-01']);
    $outside = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => '2024-12-01']);
    AgendaItem::factory()->for($earlier)->create();
    AgendaItem::factory()->for($outside)->create();

    asUser($this->member)->get(route('meetings.show', $during))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('recordNavigation.total', 2)
            ->where('recordNavigation.meetings.0.id', (string) $earlier->id)
            ->reloadOnly('recentAgendas', fn (Assert $reload) => $reload->has('recentAgendas', 1)
                ->where('recentAgendas.0.id', (string) $earlier->id)));
});
