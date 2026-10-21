<?php

namespace App\Services;

use App\Actions\GetRepOutcomeMetrics;
use App\Models\RepMetricSnapshot;
use App\Models\Tenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RepMetricSnapshotService
{
    public const int VERSION = 2;

    public function capture(CarbonInterface $day, bool $repair = false): RepMetricSnapshot
    {
        return Cache::lock('visak:outcome-capture', 600)->block(10, function () use ($day, $repair): RepMetricSnapshot {
            $at = $day->isToday() ? now()->toImmutable() : CarbonImmutable::instance($day)->startOfDay();
            $tenantIds = Tenant::query()->representational()->pluck('id')->all();

            return DB::transaction(function () use ($at, $tenantIds, $repair): RepMetricSnapshot {
                $global = null;
                foreach ([null, ...$tenantIds] as $tenantId) {
                    $row = RepMetricSnapshot::query()->firstOrNew([
                        'taken_on' => $at->toDateString(), 'scope_key' => $tenantId === null ? 'global' : 'tenant:'.$tenantId,
                        'window_days' => GetRepOutcomeMetrics::SNAPSHOT_WINDOW_DAYS, 'definition_version' => self::VERSION,
                    ]);
                    if (! $row->exists || ($repair && $row->is_reconstructed)) {
                        $measurement = GetRepOutcomeMetrics::measure($at->subDays(GetRepOutcomeMetrics::SNAPSHOT_WINDOW_DAYS), $at, $at->isToday(), $tenantId === null ? null : [$tenantId]);
                        $row->fill([...$measurement, 'tenant_id' => $tenantId, 'measured_at' => $at, 'is_reconstructed' => ! $at->isToday()])->save();
                    }
                    if ($tenantId === null) {
                        $global = $row;
                    }
                }
                RepStatisticsCache::invalidate(history: true);

                return $global;
            });
        });
    }
}
