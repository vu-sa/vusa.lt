<?php

use App\Settings\SeasonSettings;
use App\Support\Season;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.timezone' => 'Europe/Vilnius']);
});

test('a disabled season is not current', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-12-10 12:00:00', 'Europe/Vilnius'));

    expect(app(Season::class)->current())->toBeNull();
});

test('season boundaries use Vilnius time and the starting year', function (string $date, ?int $year, ?bool $locked): void {
    $settings = app(SeasonSettings::class);
    $settings->enabled = true;
    $settings->save();
    $this->travelTo(CarbonImmutable::parse($date, 'Europe/Vilnius'));

    $season = app(Season::class)->current();

    expect($season?->year)->toBe($year)
        ->and($season?->treeLocked(now()))->toBe($locked);
})->with([
    'before opening' => ['2026-11-30 23:59:00', null, null],
    'opening midnight' => ['2026-12-01 00:00:00', 2026, false],
    'before tree lock' => ['2026-12-24 23:59:00', 2026, false],
    'tree lock midnight' => ['2026-12-25 00:00:00', 2026, true],
    'last evening' => ['2027-01-06 23:59:00', 2026, true],
    'closing midnight' => ['2027-01-07 00:00:00', null, null],
]);

test('forYear resolves configured dates even while disabled', function (): void {
    $settings = app(SeasonSettings::class);
    $settings->starts_on = '11-15';
    $settings->tree_locks_on = '01-02';
    $settings->ends_on = '02-10';
    $settings->save();

    $season = app(Season::class)->forYear(2026);

    expect($season->startsAt->toDateTimeString())->toBe('2026-11-15 00:00:00')
        ->and($season->treeLocksAt->toDateTimeString())->toBe('2027-01-02 00:00:00')
        ->and($season->endsAt->toDateTimeString())->toBe('2027-02-11 00:00:00')
        ->and($season->startsAt->timezoneName)->toBe('Europe/Vilnius')
        ->and($season->isActive(CarbonImmutable::parse('2027-02-10 23:59:59', 'Europe/Vilnius')))->toBeTrue()
        ->and($season->toArray(CarbonImmutable::parse('2027-01-02', 'Europe/Vilnius')))->toBe([
            'key' => 'advent', 'year' => 2026, 'treeLocked' => true,
        ]);
});

test('current respects custom dates within one calendar year', function (): void {
    $settings = app(SeasonSettings::class);
    $settings->enabled = true;
    $settings->starts_on = '06-01';
    $settings->tree_locks_on = '06-15';
    $settings->ends_on = '06-30';
    $settings->save();
    $resolver = app(Season::class);

    expect($resolver->current(CarbonImmutable::parse('2026-05-31 23:59:59', 'Europe/Vilnius')))->toBeNull()
        ->and($resolver->current(CarbonImmutable::parse('2026-06-01', 'Europe/Vilnius'))?->year)->toBe(2026)
        ->and($resolver->current(CarbonImmutable::parse('2026-07-01', 'Europe/Vilnius')))->toBeNull();
});

test('an explicit instant is converted to the application timezone before choosing the year', function (): void {
    $settings = app(SeasonSettings::class);
    $settings->enabled = true;
    $settings->starts_on = '01-01';
    $settings->tree_locks_on = '01-02';
    $settings->ends_on = '01-06';
    $settings->save();

    expect(app(Season::class)->current(CarbonImmutable::parse('2026-12-31 22:00:00', 'UTC'))?->year)->toBe(2027);
});
