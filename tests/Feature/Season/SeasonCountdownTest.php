<?php

use App\Models\Tenant;
use App\Settings\SeasonSettings;
use App\Support\Season;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.timezone' => 'Europe/Vilnius']);
    $settings = app(SeasonSettings::class);
    $settings->enabled = true;
    $settings->save();
});

test('the countdown runs the last week before the start, in Vilnius time', function (string $date, bool $counting): void {
    $this->travelTo(CarbonImmutable::parse($date, 'Europe/Vilnius'));

    expect(app(Season::class)->countdown())
        ->toBe($counting ? ['startsAt' => '2026-12-01T00:00:00+02:00'] : null);
})->with([
    'just over a week before' => ['2026-11-23 23:59:00', false],
    'a week before, at midnight' => ['2026-11-24 00:00:00', true],
    'the last minute' => ['2026-11-30 23:59:00', true],
    'the start' => ['2026-12-01 00:00:00', false],
    'in season' => ['2026-12-10 12:00:00', false],
]);

test('the countdown finds a start in the next calendar year', function (): void {
    $settings = app(SeasonSettings::class);
    $settings->starts_on = '01-03';
    $settings->tree_locks_on = '01-20';
    $settings->ends_on = '02-01';
    $settings->save();
    $this->travelTo(CarbonImmutable::parse('2026-12-30 09:00:00', 'Europe/Vilnius'));

    expect(app(Season::class)->countdown())->toBe(['startsAt' => '2027-01-03T00:00:00+02:00']);
});

test('there is no countdown while the season is switched off', function (): void {
    $settings = app(SeasonSettings::class);
    $settings->enabled = false;
    $settings->save();
    $this->travelTo(CarbonImmutable::parse('2026-11-28 12:00:00', 'Europe/Vilnius'));

    expect(app(Season::class)->countdown())->toBeNull();
});

test('Pradžia carries the countdown only in the week before the season', function (string $date, ?array $countdown): void {
    $this->travelTo(CarbonImmutable::parse($date, 'Europe/Vilnius'));

    asUserWithInertia(makeUser(Tenant::query()->first()))->get(route('dashboard'))->assertOk()
        ->assertInertia(fn (Assert $page) => $countdown === null
            ? $page->component('Admin/ShowAdminHome')->where('seasonCountdown', null)
            : $page->component('Admin/ShowAdminHome')->where('seasonCountdown', $countdown));
})->with([
    'a week out' => ['2026-11-24 08:00:00', ['startsAt' => '2026-12-01T00:00:00+02:00']],
    'a month out' => ['2026-10-30 08:00:00', null],
]);
