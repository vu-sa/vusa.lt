<?php

use App\Support\QuietHours;
use Carbon\CarbonImmutable;

function vilnius(string $datetime): CarbonImmutable
{
    return CarbonImmutable::parse($datetime, 'Europe/Vilnius');
}

test('quiet hours run from 22:00 up to 07:00', function (string $at, bool $quiet): void {
    expect(QuietHours::isQuiet(vilnius($at)))->toBe($quiet);
})->with([
    '21:59 is not quiet' => ['2026-09-20 21:59:00', false],
    '22:00 is quiet' => ['2026-09-20 22:00:00', true],
    'midnight is quiet' => ['2026-09-21 00:00:00', true],
    '06:59 is quiet' => ['2026-09-21 06:59:00', true],
    '07:00 is not quiet' => ['2026-09-21 07:00:00', false],
]);

test('the window is read in the app timezone, not the timestamp\'s', function (): void {
    // 23:30 in Vilnius, expressed in UTC (20:30, which would not be quiet by its own clock).
    expect(QuietHours::isQuiet(vilnius('2026-09-20 23:30:00')->setTimezone('UTC')))->toBeTrue();
});

test('the next end is the coming 07:00', function (string $at, string $end): void {
    expect(QuietHours::nextEnd(vilnius($at))->format('Y-m-d H:i'))->toBe($end);
})->with([
    'evening waits until tomorrow morning' => ['2026-09-20 22:30:00', '2026-09-21 07:00'],
    'small hours wait until this morning' => ['2026-09-21 03:00:00', '2026-09-21 07:00'],
    'a quiet moment ends exactly at 07:00' => ['2026-09-21 06:59:59', '2026-09-21 07:00'],
    'outside quiet hours nothing is held' => ['2026-09-21 12:00:00', '2026-09-21 12:00'],
]);

test('the end stays 07:00 across the autumn clock change', function (): void {
    // Vilnius falls back on 2026-10-25: the night is an hour longer, the end is still 07:00 local.
    expect(QuietHours::nextEnd(vilnius('2026-10-24 23:00:00'))->format('Y-m-d H:i'))->toBe('2026-10-25 07:00');
});
