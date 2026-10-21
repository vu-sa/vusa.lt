<template>
  <div class="overflow-x-auto border-b border-border">
    <table data-testid="tenant-rep-metrics-table" class="w-full text-sm">
      <thead>
        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          <th scope="col" class="py-2 pr-3">
            {{ $t('metrics.table.metric') }}
          </th>
          <th scope="col" class="px-2 py-2 text-right">
            {{ $t('metrics.table.current_short') }}
            <span class="block whitespace-nowrap font-normal">{{ currentPeriodLabel }}</span>
          </th>
          <th scope="col" class="py-2 pl-2 text-right">
            {{ $t('metrics.table.comparison_short') }}
            <span class="block whitespace-nowrap font-normal">{{ baselinePeriodLabel }}</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-border">
        <tr v-for="metric in metrics" :key="metric.key" :data-metric="metric.key">
          <th scope="row" class="py-3 pr-3 text-left font-medium">
            {{ $t(`metrics.metrics.${metric.key}`) }}
            <span :class="['block text-xs font-normal', repMetricStatusClass(metric)]" data-slot="metric-status">
              {{ $t(`metrics.status.${repMetricStatus(metric)}`) }}
            </span>
          </th>
          <td class="px-2 py-3 text-right font-semibold tabular-nums">
            {{ percent(metric.now) }}
            <span
              v-if="trendSince && repMetricTrend(metric)"
              :class="['ml-1 whitespace-nowrap text-xs font-normal', repMetricTrendClass(repMetricTrend(metric)!.direction)]"
              :title="$t('metrics.trend.since', { date: formatDate(trendSince), value: percent(metric.previous ?? null) })"
              data-slot="metric-trend"
              :data-direction="repMetricTrend(metric)!.direction"
            >
              <span aria-hidden="true">{{ trendGlyph[repMetricTrend(metric)!.direction] }}{{ repMetricTrend(metric)!.direction === 'flat' ? '' : Math.abs(repMetricTrend(metric)!.delta) }}</span>
              <span class="sr-only">{{ $t(`metrics.trend.${repMetricTrend(metric)!.direction}`) }} {{ repMetricTrend(metric)!.direction === 'flat' ? '' : formatDelta(repMetricTrend(metric)!.delta) }}</span>
            </span>
            <span v-if="metric.whole > 0" class="block whitespace-nowrap text-xs font-normal text-muted-foreground" data-slot="metric-counted">
              {{ metric.part }} / {{ metric.whole }}
            </span>
          </td>
          <td class="py-3 pl-2 text-right tabular-nums">
            {{ percent(metric.baseline) }}
            <span v-if="metric.baselineWhole > 0" class="block whitespace-nowrap text-xs text-muted-foreground" data-slot="baseline-counted">
              {{ metric.baselinePart }} / {{ metric.baselineWhole }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import { repMetricStatus, repMetricStatusClass, repMetricTrend, repMetricTrendClass } from './repMetricStatus';

import { formatDate } from '@/Utils/dateTime';

interface Metric {
  key: string;
  now: number | null;
  part: number;
  whole: number;
  baseline: number | null;
  baselinePart: number;
  baselineWhole: number;
  previous?: number | null;
}

defineProps<{ metrics: Metric[]; currentPeriodLabel: string; baselinePeriodLabel: string; trendSince?: string | null }>();

const trendGlyph = { up: '▲', down: '▼', flat: '–' };
const formatDelta = (delta: number) => $t('metrics.trend.points', { delta: `${delta > 0 ? '+' : '−'}${Math.abs(delta)}` });

const percent = (value: number | null) => value === null ? '—' : `${value} %`;
</script>
