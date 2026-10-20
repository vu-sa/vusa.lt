<?php

namespace App\Console\Commands;

use App\Models\RepMetricSnapshot;
use App\Services\InstitutionStatusSnapshotService;
use App\Services\RepMetricSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Description('Capture representative metrics and close institution status history')]
#[Signature('metrics:reps:snapshot
    {--date= : Capture a reference date (YYYY-MM-DD)}
    {--from= : Fill missing status and outcome snapshots from this date}
    {--repair-reconstructed : Replace reconstructed snapshots only}')]
class RepMetricSnapshotCommand extends Command
{
    public function handle(InstitutionStatusSnapshotService $statuses, RepMetricSnapshotService $outcomes): int
    {
        $input = array_filter(['date' => $this->option('date'), 'from' => $this->option('from')], fn ($value) => $value !== null);
        $validator = Validator::make($input, [
            'date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'from' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());

            return self::FAILURE;
        }
        $day = CarbonImmutable::parse($input['date'] ?? today()->toDateString());
        $repair = (bool) $this->option('repair-reconstructed');
        $from = isset($input['from']) ? CarbonImmutable::parse($input['from']) : null;
        $recoveryFrom = $from;
        if ($recoveryFrom === null && ! isset($input['date'])) {
            $latest = RepMetricSnapshot::query()->where('scope_key', 'global')->where('definition_version', RepMetricSnapshotService::VERSION)->max('taken_on');
            $recoveryFrom = $latest ? CarbonImmutable::parse($latest)->addDay() : $day;
        }
        $this->captureOutcomes($outcomes, $recoveryFrom, $repair);
        $snapshot = $outcomes->capture($day, $repair);
        $this->info('Stored outcome metrics for '.$snapshot->taken_on->toDateString().'.');

        if (! isset($input['date']) || $from !== null) {
            $start = $from ?? CarbonImmutable::today()->subDays(180);
            $end = CarbonImmutable::today()->subDay();
            $bar = $this->output->createProgressBar((int) $start->diffInDays($end) + 1);
            $written = $statuses->capture($start, $end, reconstructed: $from !== null, repair: $repair, progress: fn () => $bar->advance());
            $bar->finish();
            $this->newLine();
            $this->info(sprintf('Wrote %d tenant-days; existing captured days were preserved.', $written));
        }

        return self::SUCCESS;
    }

    private function captureOutcomes(RepMetricSnapshotService $outcomes, ?CarbonImmutable $recoveryFrom, bool $repair): void
    {
        if ($recoveryFrom !== null && $recoveryFrom->lte(today())) {
            $bar = $this->output->createProgressBar((int) $recoveryFrom->diffInDays(today()) + 1);
            for ($cursor = $recoveryFrom; $cursor->lte(today()); $cursor = $cursor->addDay()) {
                $outcomes->capture($cursor, $repair);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
        }
    }
}
