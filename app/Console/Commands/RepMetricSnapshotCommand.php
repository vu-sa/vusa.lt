<?php

namespace App\Console\Commands;

use App\Actions\GetRepOutcomeMetrics;
use App\Models\RepMetricSnapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Store the rep outcome metrics for one day, measured over the days before it. Run daily by the
 * scheduler; `--mark-baseline` makes that day the reference every later number is compared with.
 */
#[Description('Store today\'s student representative outcome metrics, optionally as the new baseline')]
#[Signature('metrics:reps:snapshot
    {--date= : Back-fill a past day (YYYY-MM-DD); active reps cannot be measured for it}
    {--mark-baseline : Make this snapshot the baseline}')]
class RepMetricSnapshotCommand extends Command
{
    public function handle(): int
    {
        $date = $this->option('date');
        $day = $date ? Carbon::parse($date)->startOfDay() : today();

        if ($day->isFuture()) {
            $this->error('A snapshot cannot be taken for a future day.');

            return self::FAILURE;
        }

        $window = GetRepOutcomeMetrics::SNAPSHOT_WINDOW_DAYS;
        $metrics = GetRepOutcomeMetrics::forWindow(
            $day->copy()->subDays($window),
            $day->isToday() ? now() : $day,
            withActiveReps: $day->isToday(),
        );

        $snapshot = DB::transaction(function () use ($day, $window, $metrics): RepMetricSnapshot {
            if ($this->option('mark-baseline')) {
                RepMetricSnapshot::query()->where('is_baseline', true)->update(['is_baseline' => false]);
            }

            $snapshot = RepMetricSnapshot::query()->firstOrNew(['taken_on' => $day->toDateString()]);
            $snapshot->fill([
                'window_days' => $window,
                'metrics' => $metrics,
                'is_baseline' => $this->option('mark-baseline') || $snapshot->is_baseline,
            ])->save();

            return $snapshot;
        });

        $this->table(
            ['Metric', 'Value', 'Counted'],
            collect($metrics)->map(fn (array $metric, string $key): array => [
                $key,
                $metric['value'] === null ? '—' : $metric['value'].' %',
                $metric['part'].' / '.$metric['whole'],
            ])->values()->all(),
        );

        $this->info(sprintf(
            'Stored the %s snapshot (%d days before it)%s.',
            $day->toDateString(),
            $window,
            $snapshot->is_baseline ? ' as the baseline' : '',
        ));

        return self::SUCCESS;
    }
}
