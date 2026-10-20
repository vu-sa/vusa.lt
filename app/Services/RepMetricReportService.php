<?php

namespace App\Services;

use App\Actions\GetRepOutcomeMetrics;
use App\Models\Cadence;
use App\Settings\RepMetricSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

class RepMetricReportService
{
    /** How far back the arrow on each metric looks. */
    public const int TREND_DAYS = 14;

    public const array METRICS = ['recorded_within_week', 'agenda_completion', 'task_completion'];

    public function currentCadence(): ?Cadence
    {
        return Cadence::query()->globalLadder()->containing(now('Europe/Vilnius')->toDateString())
            ->latest('start_date')->first();
    }

    /** @return Builder<Cadence> */
    public function baselineOptions(): Builder
    {
        $current = $this->currentCadence();

        return Cadence::query()->globalLadder()
            ->whereDate('end_date', '<', $current?->start_date->toDateString() ?? now('Europe/Vilnius')->toDateString())
            ->latest('end_date');
    }

    public function baselineCadence(): ?Cadence
    {
        $id = app(RepMetricSettings::class)->baseline_cadence_id;

        return $id === null ? $this->baselineOptions()->first() : $this->baselineOptions()->whereKey($id)->first();
    }

    /** @param list<int>|null $tenantIds */
    public function report(?array $tenantIds = null): array
    {
        $now = CarbonImmutable::now('Europe/Vilnius');
        $current = $this->currentCadence();
        $baseline = $this->baselineCadence();
        $measurement = $current ? GetRepOutcomeMetrics::forPeriod($this->boundary($current->start_date->toDateString()), $now, $tenantIds) : [];
        $trendSince = $current && $now->subDays(self::TREND_DAYS)->startOfDay()->gt($this->boundary($current->start_date->toDateString()))
            ? $now->subDays(self::TREND_DAYS)->startOfDay()
            : null;
        $earlier = $current && $trendSince ? $this->earlierMeasurement($current, $trendSince, $tenantIds) : [];
        $reference = $baseline ? $this->baselineMeasurement($baseline, $tenantIds) : [];

        return [
            'measuredAt' => $now->toIso8601String(),
            'currentPeriod' => $current ? $this->period($current, $now->toDateString()) : null,
            'baselinePeriod' => $baseline ? $this->period($baseline, $baseline->end_date->toDateString()) : null,
            'trendSince' => $trendSince?->toDateString(),
            'metrics' => array_map(fn (string $key): array => [
                'key' => $key,
                'now' => $measurement[$key]['value'] ?? null,
                'part' => $measurement[$key]['part'] ?? 0,
                'whole' => $measurement[$key]['whole'] ?? 0,
                'baseline' => $reference[$key]['value'] ?? null,
                'baselinePart' => $reference[$key]['part'] ?? 0,
                'baselineWhole' => $reference[$key]['whole'] ?? 0,
                'previous' => $earlier[$key]['value'] ?? null,
                'previousPart' => $earlier[$key]['part'] ?? 0,
                'previousWhole' => $earlier[$key]['whole'] ?? 0,
            ], self::METRICS),
        ];
    }

    /**
     * Retrospective cohorts follow late entries and settings, so the baseline invalidates with the
     * same writes as the current figure; the longer TTL only covers quiet periods.
     *
     * @param  list<int>|null  $tenantIds
     */
    private function baselineMeasurement(Cadence $baseline, ?array $tenantIds): array
    {
        $from = $baseline->start_date->toDateString();
        $until = $baseline->end_date->toDateString();

        return RepStatisticsCache::remember(
            "baseline:{$baseline->id}:{$from}:{$until}:".implode(',', $tenantIds ?? []),
            fn (): array => GetRepOutcomeMetrics::forPeriod($this->boundary($from), $this->boundary($until)->addDay(), $tenantIds),
            tenantIds: $tenantIds,
            ttl: 3600,
        );
    }

    /**
     * The current cadence as it stood at the end of $trendSince, so the arrow compares like with
     * like: it must see the same late entries and settings as the current figure.
     *
     * @param  list<int>|null  $tenantIds
     */
    private function earlierMeasurement(Cadence $current, CarbonImmutable $trendSince, ?array $tenantIds): array
    {
        $from = $current->start_date->toDateString();

        return RepStatisticsCache::remember(
            "trend:{$current->id}:{$from}:{$trendSince->toDateString()}:".implode(',', $tenantIds ?? []),
            fn (): array => GetRepOutcomeMetrics::forPeriod($this->boundary($from), $trendSince->addDay(), $tenantIds),
            tenantIds: $tenantIds,
        );
    }

    private function boundary(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date, 'Europe/Vilnius')->startOfDay();
    }

    private function period(Cadence $cadence, string $until): array
    {
        return ['cadenceId' => $cadence->id, 'label' => $cadence->label, 'from' => $cadence->start_date->toDateString(), 'until' => $until];
    }
}
