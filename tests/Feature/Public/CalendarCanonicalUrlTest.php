<?php

use App\Models\Calendar;
use App\Models\PublicUrl;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ],
    );
});

test('changing the permalink demotes the old one to a legacy redirect, without touching public_urls before that', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'permalink' => ['lt' => 'pirmas-pavadinimas', 'en' => ''],
        'is_draft' => false,
        'date' => '2026-03-10',
    ]);

    // Nothing is written just from creating the event — there's no "old" url yet, so nothing to
    // preserve. public_urls only ever holds retired permalinks.
    expect(PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->count())->toBe(0);

    $oldUrl = $event->publicUrl('lt');

    $event->update(['permalink' => ['lt' => 'naujas-pavadinimas', 'en' => '']]);
    $event->refresh();

    $newUrl = $event->publicUrl('lt');

    expect($newUrl)->not()->toBe($oldUrl)
        ->and($newUrl)->toEndWith('/lt/kalendorius/2026/naujas-pavadinimas');

    $this->get($oldUrl)->assertStatus(301)->assertRedirect($newUrl);
    $this->get($newUrl)->assertStatus(200);
});

test('re-adopting a demoted permalink resolves live again, without duplicating the retired row', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'permalink' => ['lt' => 'senas', 'en' => ''],
        'is_draft' => false,
        'date' => '2026-03-10',
    ]);

    $event->update(['permalink' => ['lt' => 'naujas', 'en' => '']]);
    $event->update(['permalink' => ['lt' => 'senas', 'en' => '']]);
    $event->refresh();

    $rows = PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->get();

    // One retired row per permalink this event has ever moved away from: "senas" (retired by the
    // first change) and "naujas" (retired by reverting back). Re-adopting "senas" doesn't
    // duplicate its row — recordLegacyUrl() only fires when the *current* value changes away
    // from it, and "senas" is live again, not retired.
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('url'))->toContain($event->publicUrl('lt'))
        ->and($event->publicUrl('lt'))->toEndWith('/lt/kalendorius/2026/senas');
});

test('the legacy y/m/d route resolves an event that has never written a single public_urls row', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'title' => ['lt' => 'Atvira paskaita', 'en' => ''],
        'permalink' => ['lt' => 'atvira-paskaita', 'en' => ''],
        'is_draft' => false,
        'date' => '2026-04-05',
    ]);

    // Simulate an event that predates this feature: no row at all, canonical or otherwise.
    PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->delete();

    $currentUrl = route('calendar.show', [
        'lang' => 'lt', 'year' => 2026, 'permalink' => 'atvira-paskaita',
    ]);

    $this->get(route('calendar.event.legacy', [
        'lang' => 'lt', 'year' => 2026, 'month' => 4, 'day' => 5, 'slug' => 'atvira-paskaita',
    ]))->assertStatus(301)->assertRedirect($currentUrl);

    expect(PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->exists())->toBeFalse();
});

test('the id-based redirect route reads straight from the stored permalink, with no public_urls row needed', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'permalink' => ['lt' => 'be-viesu-urls', 'en' => ''],
        'is_draft' => false,
        'date' => '2026-05-01',
    ]);

    PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->delete();

    $this->get(route('calendar.event', ['lang' => 'lt', 'calendar' => $event->id]))
        ->assertStatus(301)
        ->assertRedirect(route('calendar.show', ['lang' => 'lt', 'year' => 2026, 'permalink' => 'be-viesu-urls']));
});

test('two events with the same title in different years each get their own year-scoped url', function (): void {
    $first = Calendar::factory()->for($this->tenant)->create([
        'title' => ['lt' => 'Metinis renginys', 'en' => ''],
        'permalink' => ['lt' => 'metinis-renginys', 'en' => ''],
        'is_draft' => false,
        'date' => '2025-09-01',
    ]);
    $second = Calendar::factory()->for($this->tenant)->create([
        'title' => ['lt' => 'Metinis renginys', 'en' => ''],
        'permalink' => ['lt' => 'metinis-renginys', 'en' => ''],
        'is_draft' => false,
        'date' => '2026-09-01',
    ]);

    expect($first->publicUrl('lt'))->toEndWith('/lt/kalendorius/2025/metinis-renginys')
        ->and($second->publicUrl('lt'))->toEndWith('/lt/kalendorius/2026/metinis-renginys');
});

test('the event list exposes each event\'s permalink-based canonical url, built without a public_urls query', function (): void {
    $event = Calendar::factory()->for($this->tenant)->create([
        'permalink' => ['lt' => 'atviru-duru-diena', 'en' => ''],
        'is_draft' => false,
        'date' => now()->addDay(),
    ]);

    // No public_urls rows at all — the list must build the URL from permalink + date directly.
    PublicUrl::query()->where('urlable_type', $event->getMorphClass())->where('urlable_id', $event->id)->delete();

    $expectedUrl = route('calendar.show', [
        'lang' => 'lt', 'year' => now()->addDay()->format('Y'), 'permalink' => 'atviru-duru-diena',
    ]);

    $this->get(route('calendar.list', ['calendarString' => 'kalendorius', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/CalendarEventList')
            ->where('events.data.0.public_url', $expectedUrl)
        );
});

test('the tenant filter options include a tenant with only past events, even on the upcoming tab', function (): void {
    $mif = Tenant::firstOrCreate(
        ['alias' => 'mif'],
        [
            'shortname' => 'VU SA MIF',
            'shortname_vu' => 'MIF',
            'fullname' => 'VU SA Matematikos ir informatikos fakultetas',
            'type' => 'padalinys',
        ],
    );

    Calendar::factory()->for($mif)->create([
        'is_draft' => false,
        'date' => now()->subMonth(),
    ]);

    // Tab switching happens client-side against Typesense with no Inertia reload, so the
    // "upcoming" tab's initial props must not be scoped to only tenants with an upcoming
    // event — MIF (past-only here) still has to be a selectable filter option.
    $this->get(route('calendar.list', ['calendarString' => 'kalendorius', 'lang' => 'lt', 'tab' => 'upcoming']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/CalendarEventList')
            ->where('allTenants', fn ($tenants) => $tenants->pluck('shortname')->contains('VU SA MIF'))
        );
});
