<?php

namespace App\Console\Commands;

use App\Services\RepMetricReportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Compare student representative metrics across global cadencies')]
#[Signature('metrics:reps')]
class RepMetricsCommand extends Command
{
    public function handle(RepMetricReportService $reports): int
    {
        $report = $reports->report();
        foreach (['currentPeriod' => 'Current cadency', 'baselinePeriod' => 'Comparison cadency'] as $key => $label) {
            $period = $report[$key];
            $this->line($period ? "{$label}: {$period['from']} – {$period['until']}" : "{$label}: unavailable; check Representation settings.");
        }
        $this->table(['Metric', 'Current', 'Count', 'Comparison', 'Count'], array_map(fn (array $metric): array => [
            $metric['key'], $this->percent($metric['now']), "{$metric['part']} / {$metric['whole']}",
            $this->percent($metric['baseline']), "{$metric['baselinePart']} / {$metric['baselineWhole']}",
        ], $report['metrics']));
        $this->line('Retrospective calculation from currently retained records; later corrections can change both periods.');

        return self::SUCCESS;
    }

    private function percent(?float $value): string
    {
        return $value === null ? '—' : $value.' %';
    }
}
