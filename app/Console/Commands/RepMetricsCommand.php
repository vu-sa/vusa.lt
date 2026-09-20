<?php

namespace App\Console\Commands;

use App\Actions\GetRepOutcomeMetrics;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Print the rep outcome metrics (U25) with their Phase 0 baseline and target, then the monthly trend.
 * Phase 10.3 re-runs this to compare with the baseline in `.ai/redesign/admin/plan.md`.
 */
#[Description('Report the student representative outcome metrics with a monthly trend')]
#[Signature('metrics:reps {--months=12 : How many months the trend covers}')]
class RepMetricsCommand extends Command
{
    public function handle(): int
    {
        $report = GetRepOutcomeMetrics::execute(max(1, (int) $this->option('months')));
        $rate = GetRepOutcomeMetrics::rate(...);

        $tasks = $report['tasks'];

        $this->table(
            ['Metric', 'Now', 'Baseline', 'Target'],
            collect($report['now'])->map(fn (?float $value, string $metric): array => [
                $metric,
                $this->percent($value),
                $this->percent($report['targets'][$metric]['baseline']),
                $this->percent($report['targets'][$metric]['target']),
            ])->values()->all(),
        );

        $this->newLine();
        $this->table(
            ['Month', 'Meetings', '≤ 7 d', 'Agenda items', 'With votes', 'By a rep'],
            collect($report['months'])->map(fn (string $month): array => [
                $month,
                $report['meetings'][$month]['total'],
                $this->percent($rate($report['meetings'][$month]['withinWeek'], $report['meetings'][$month]['total'])),
                $report['meetings'][$month]['items'],
                $this->percent($rate($report['meetings'][$month]['itemsWithVotes'], $report['meetings'][$month]['items'])),
                $this->percent($rate($report['meetings'][$month]['byRep'], $report['meetings'][$month]['known'])),
            ])->all(),
        );

        $this->newLine();
        $this->table(
            ['Task type', 'Tasks', 'Completed', 'Rate', 'Median days'],
            collect($tasks)->map(fn (array $type, string $name): array => [
                $name,
                $type['total'],
                $type['completed'],
                $this->percent($rate($type['completed'], $type['total'])),
                $type['medianDays'] ?? '—',
            ])->values()->all(),
        );

        $this->warn('Reps active in the last 30 days is a snapshot: users.last_action keeps no history.');

        return self::SUCCESS;
    }

    private function percent(?float $value): string
    {
        return $value === null ? '—' : $value.' %';
    }
}
