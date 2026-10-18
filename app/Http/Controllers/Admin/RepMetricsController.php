<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetRepOutcomeMetrics;
use App\Http\Controllers\AdminController;
use App\Models\RepMetricSnapshot;
use App\Models\Role;
use Inertia\Inertia;

/**
 * The rep outcome metrics (U25) under Sistema: is the loop getting shorter?
 *
 * Gated like the system status and the mail queue, the other pages that read the platform's own
 * numbers. The report reads a year of meetings and tasks, so it is deferred behind a skeleton.
 */
class RepMetricsController extends AdminController
{
    private const int MONTHS = 12;

    public function index()
    {
        $this->handleAuthorization('viewAny', Role::class);

        return $this->inertiaResponse('Admin/ShowRepMetrics', [
            'report' => Inertia::defer(fn (): array => $this->present(GetRepOutcomeMetrics::execute(self::MONTHS)), 'secondary'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{months: list<string>, windowDays: int, baseline: array{takenOn: string, windowDays: int}|null, metrics: list<array<string, mixed>>, tasks: list<array<string, mixed>>}
     */
    private function present(array $report): array
    {
        $months = $report['months'];
        $rate = GetRepOutcomeMetrics::rate(...);

        $trend = fn (callable $value): array => array_map(
            fn (string $month): array => ['month' => $month, 'value' => $value($month)],
            $months,
        );

        $meetings = $report['meetings'];
        $taskMonth = fn (string $type, string $month): ?float => isset($report['tasks'][$type])
            ? $rate($report['tasks'][$type]['monthly'][$month]['completed'], $report['tasks'][$type]['monthly'][$month]['total'])
            : null;
        $allTasksMonth = fn (string $month): ?float => $rate(
            array_sum(array_map(fn (array $type): int => $type['monthly'][$month]['completed'], $report['tasks'])),
            array_sum(array_map(fn (array $type): int => $type['monthly'][$month]['total'], $report['tasks'])),
        );

        $metrics = [
            [
                'key' => 'recorded_within_week',
                'trend' => $trend(fn (string $month): ?float => $rate($meetings[$month]['withinWeek'], $meetings[$month]['total'])),
            ],
            [
                'key' => 'vote_information',
                'trend' => $trend(fn (string $month): ?float => $rate($meetings[$month]['itemsWithVotes'], $meetings[$month]['items'])),
            ],
            [
                'key' => 'task_completion',
                'trend' => $trend($allTasksMonth),
            ],
            [
                'key' => 'periodicity_gap',
                'trend' => $trend(fn (string $month): ?float => $taskMonth('periodicity_gap', $month)),
            ],
            // users.last_action keeps no history, so only the stored snapshots can trend it.
            ['key' => 'active_reps', 'trend' => $this->snapshotTrend('active_reps', $months)],
            [
                'key' => 'own_recording',
                'trend' => $trend(fn (string $month): ?float => $rate($meetings[$month]['byRep'], $meetings[$month]['known'])),
            ],
        ];

        $windowDays = GetRepOutcomeMetrics::SNAPSHOT_WINDOW_DAYS;
        $now = GetRepOutcomeMetrics::forWindow(now()->subDays($windowDays), now());
        $baseline = RepMetricSnapshot::baseline();

        return [
            'months' => $months,
            'windowDays' => $windowDays,
            'baseline' => $baseline
                ? ['takenOn' => $baseline->taken_on->toDateString(), 'windowDays' => $baseline->window_days]
                : null,
            'metrics' => array_map(fn (array $metric): array => [
                'now' => $now[$metric['key']]['value'] ?? null,
                'baseline' => $baseline?->value($metric['key']),
                'target' => GetRepOutcomeMetrics::TARGETS[$metric['key']] ?? null,
                ...$metric,
            ], $metrics),
            'tasks' => array_map(fn (array $type, string $name): array => [
                'actionType' => $name,
                'total' => $type['total'],
                'completed' => $type['completed'],
                'rate' => $rate($type['completed'], $type['total']),
                'medianDays' => $type['medianDays'],
            ], $report['tasks'], array_keys($report['tasks'])),
        ];
    }

    /**
     * Each month's latest daily snapshot, or null when none was taken in the range. Only daily
     * snapshots count: a baseline measured over another window would not be comparable.
     *
     * @param  list<string>  $months
     * @return list<array{month: string, value: float|null}>|null
     */
    private function snapshotTrend(string $metric, array $months): ?array
    {
        $byMonth = RepMetricSnapshot::query()
            ->where('window_days', GetRepOutcomeMetrics::SNAPSHOT_WINDOW_DAYS)
            ->whereDate('taken_on', '>=', $months[0].'-01')
            ->orderBy('taken_on')
            ->get()
            ->filter(fn (RepMetricSnapshot $snapshot): bool => $snapshot->value($metric) !== null)
            ->keyBy(fn (RepMetricSnapshot $snapshot): string => $snapshot->taken_on->format('Y-m'));

        if ($byMonth->isEmpty()) {
            return null;
        }

        return array_map(fn (string $month): array => [
            'month' => $month,
            'value' => $byMonth->get($month)?->value($metric),
        ], $months);
    }
}
