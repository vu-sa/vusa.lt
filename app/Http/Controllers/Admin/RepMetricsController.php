<?php

namespace App\Http\Controllers\Admin;

use App\Actions\GetRepOutcomeMetrics;
use App\Http\Controllers\AdminController;
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
     * @return array{months: list<string>, metrics: list<array<string, mixed>>, tasks: list<array<string, mixed>>}
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
        $sumMeetings = fn (string $key): int => array_sum(array_column($meetings, $key));
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
            // A snapshot: users.last_action keeps no history, so there is nothing to trend.
            ['key' => 'active_reps', 'trend' => null],
            [
                'key' => 'own_recording',
                'now' => $rate($sumMeetings('byRep'), $sumMeetings('known')),
                'trend' => $trend(fn (string $month): ?float => $rate($meetings[$month]['byRep'], $meetings[$month]['known'])),
            ],
        ];

        return [
            'months' => $months,
            'metrics' => array_map(fn (array $metric): array => [
                'now' => $report['now'][$metric['key']] ?? null,
                'baseline' => $report['targets'][$metric['key']]['baseline'] ?? null,
                'target' => $report['targets'][$metric['key']]['target'] ?? null,
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
}
