<?php

use App\Enums\InstitutionActivityStatus;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Services\InstitutionActivityStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

function institutionForActivityStatus(
    array $meetingDates = [],
    array $checkInRanges = [],
    ?int $periodicityDays = 30,
    ?int $typePeriodicityDays = null,
): Institution {
    $institution = new Institution([
        'meeting_periodicity_days' => $periodicityDays,
    ]);

    $institution->setRelation('meetings', new Collection(
        collect($meetingDates)
            ->map(fn (string $date): Meeting => new Meeting(['start_time' => $date]))
            ->all()
    ));
    $institution->setRelation('checkIns', new Collection(
        collect($checkInRanges)
            ->map(fn (array $range): InstitutionCheckIn => new InstitutionCheckIn([
                'start_date' => $range[0],
                'end_date' => $range[1],
            ]))
            ->all()
    ));
    $institution->setRelation('types', new Collection(
        $typePeriodicityDays === null
            ? []
            : [new InstitutionType(['extra_attributes' => ['meeting_periodicity_days' => $typePeriodicityDays]])]
    ));

    return $institution;
}

describe('InstitutionActivityStatusService', function (): void {
    test('returns no activity when there are no meetings or coverage', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::NoActivity)
            ->and($status->effectiveDaysSinceActivity)->toBeNull()
            ->and($status->status->requiresAction())->toBeTrue();
    });

    test('uses effective days and excludes academic vacations', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(['2025-06-20 10:00:00']),
            CarbonImmutable::parse('2025-09-01 12:00:00'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::Healthy)
            ->and($status->effectiveDaysSinceActivity)->toBe(11);
    });

    test('becomes approaching at eighty percent of periodicity', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(['2025-10-22 10:00:00']),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::Approaching)
            ->and($status->effectiveDaysSinceActivity)->toBe(24)
            ->and($status->progressPercentage)->toBe(80);
    });

    test('is not overdue until periodicity is exceeded', function (): void {
        $service = app(InstitutionActivityStatusService::class);

        $atThreshold = $service->resolve(
            institutionForActivityStatus(['2025-10-16 10:00:00']),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );
        $pastThreshold = $service->resolve(
            institutionForActivityStatus(['2025-10-15 10:00:00']),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );

        expect($atThreshold->status)->toBe(InstitutionActivityStatus::Approaching)
            ->and($pastThreshold->status)->toBe(InstitutionActivityStatus::Overdue);
    });

    test('an upcoming meeting provides coverage and latest past meeting remains separate', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus([
                '2025-09-01 10:00:00',
                '2025-11-20 10:00:00',
                '2025-10-01 10:00:00',
            ]),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::CoveredByUpcomingMeeting)
            ->and($status->lastMeetingAt?->toDateString())->toBe('2025-10-01')
            ->and($status->nextMeetingAt?->toDateString())->toBe('2025-11-20')
            ->and($status->status->requiresAction())->toBeFalse();
    });

    test('an active check-in provides coverage and a completed check-in becomes the activity reference', function (): void {
        $service = app(InstitutionActivityStatusService::class);
        $meetingDates = ['2025-10-01 10:00:00'];
        $at = CarbonImmutable::parse('2025-11-15 12:00:00');

        $active = $service->resolve(
            institutionForActivityStatus($meetingDates, [['2025-11-01', '2025-11-30']]),
            $at,
        );
        $expired = $service->resolve(
            institutionForActivityStatus($meetingDates, [['2025-10-01', '2025-10-31']]),
            $at,
        );

        expect($active->status)->toBe(InstitutionActivityStatus::CoveredByCheckIn)
            ->and($active->activeCheckInUntil?->toDateString())->toBe('2025-11-30')
            ->and($expired->status)->toBe(InstitutionActivityStatus::RecentCheckIn)
            ->and($expired->lastActivityType)->toBe('check_in')
            ->and($expired->lastActivityAt?->toDateString())->toBe('2025-10-31')
            ->and($expired->effectiveDaysSinceActivity)->toBe(15);
    });

    test('a check-in ending before summer vacation prevents a false approaching warning', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(
                meetingDates: ['2026-05-02 15:00:00'],
                checkInRanges: [['2026-06-18', '2026-06-30']],
            ),
            CarbonImmutable::parse('2026-07-26 12:00:00'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::RecentCheckIn)
            ->and($status->lastActivityType)->toBe('check_in')
            ->and($status->lastActivityAt?->toDateString())->toBe('2026-06-30')
            ->and($status->effectiveDaysSinceActivity)->toBe(0)
            ->and($status->progressPercentage)->toBe(0);
    });

    test('inherits periodicity from the institution type', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(
                meetingDates: ['2025-11-01 10:00:00'],
                periodicityDays: null,
                typePeriodicityDays: 14,
            ),
            CarbonImmutable::parse('2025-11-15 12:00:00'),
        );

        expect($status->periodicityDays)->toBe(14)
            ->and($status->status)->toBe(InstitutionActivityStatus::Approaching);
    });

    test('completed check-ins have a half-interval grace period', function (int $periodicity, int $days, InstitutionActivityStatus $expected): void {
        $at = CarbonImmutable::parse('2026-11-20 12:00:00');
        $end = $at->subDays($days)->toDateString();
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus([], [['2026-09-01', $end]], $periodicity),
            $at,
        );

        expect($status->status)->toBe($expected)
            ->and($status->effectiveDaysSinceActivity)->toBe($days)
            ->and($status->lastActivityType)->toBe('check_in')
            ->and($status->lastCompletedCheckInAt?->toDateString())->toBe($end)
            ->and($status->progressPercentage)->toBe((int) round($days / ceil($periodicity / 2) * 100))
            ->and($status->status->requiresAction())->toBe($expected === InstitutionActivityStatus::Overdue);
    })->with([
        'first uncovered day' => [30, 1, InstitutionActivityStatus::RecentCheckIn],
        'last grace day' => [30, 15, InstitutionActivityStatus::RecentCheckIn],
        'first overdue day' => [30, 16, InstitutionActivityStatus::Overdue],
        'odd interval rounds up' => [31, 16, InstitutionActivityStatus::RecentCheckIn],
        'odd interval overdue' => [31, 17, InstitutionActivityStatus::Overdue],
        'minimum grace' => [1, 1, InstitutionActivityStatus::RecentCheckIn],
    ]);

    test('meeting coverage takes precedence over a newer completed check-in', function (int $meetingAge, InstitutionActivityStatus $expected): void {
        $at = CarbonImmutable::parse('2026-11-20 12:00:00');
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(
                [$at->subDays($meetingAge)->toDateTimeString()],
                [['2026-11-18', '2026-11-18']],
            ),
            $at,
        );

        expect($status->status)->toBe($expected)
            ->and($status->lastActivityType)->toBe($meetingAge <= 30 ? 'meeting' : 'check_in')
            ->and($status->effectiveDaysSinceActivity)->toBe($meetingAge <= 30 ? $meetingAge : 2);
    })->with([
        'healthy meeting' => [10, InstitutionActivityStatus::Healthy],
        'approaching meeting' => [24, InstitutionActivityStatus::Approaching],
        'meeting at threshold' => [30, InstitutionActivityStatus::Approaching],
        'old meeting yields to check-in' => [31, InstitutionActivityStatus::RecentCheckIn],
    ]);

    test('future check-ins do not cover an overdue meeting', function (): void {
        $status = app(InstitutionActivityStatusService::class)->resolve(
            institutionForActivityStatus(['2026-09-01'], [['2026-12-01', '2026-12-10']]),
            CarbonImmutable::parse('2026-11-20'),
        );

        expect($status->status)->toBe(InstitutionActivityStatus::Overdue)
            ->and($status->lastCompletedCheckInAt)->toBeNull();
    });

    test('takes the shortest periodicity among several types', function (): void {
        $institution = institutionForActivityStatus(periodicityDays: null);
        $institution->setRelation('types', new Collection([
            new InstitutionType(['extra_attributes' => ['meeting_periodicity_days' => 60]]),
            new InstitutionType(['extra_attributes' => ['meeting_periodicity_days' => 21]]),
            new InstitutionType(['extra_attributes' => []]),
        ]));

        expect($institution->meeting_periodicity_days)->toBe(21);
    });

    test('falls back to thirty days without an override or a typed periodicity', function (): void {
        expect(institutionForActivityStatus(periodicityDays: null)->meeting_periodicity_days)->toBe(30);
    });
});
