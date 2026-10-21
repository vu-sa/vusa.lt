<template>
  <div class="space-y-3" data-slot="tenant-rep-metrics">
    <div v-if="loading && !data" class="flex flex-col gap-3" data-slot="tenant-rep-metrics-loading" aria-hidden="true">
      <Skeleton v-for="row in 3" :key="row" class="h-12 w-full" />
    </div>
    <p v-else-if="!data" class="border-y border-border py-6 text-sm text-muted-foreground">
      {{ $t('visak.tenant_overview.rep_metrics_empty') }}
    </p>
    <template v-else>
      <RepMetricTable :metrics="data.metrics" :current-period-label="compactPeriod(data.currentPeriod?.label)" :baseline-period-label="compactPeriod(data.baselinePeriod?.label)" :trend-since="data.trendSince" />
      <p v-if="data.trendSince" class="text-xs text-muted-foreground" data-slot="tenant-rep-metrics-trend-legend">
        <span aria-hidden="true">▲▼ </span>{{ $t('metrics.trend.legend', { date: formatDate(data.trendSince) }) }}
      </p>
      <details class="space-y-2 text-xs text-muted-foreground">
        <summary class="cursor-pointer py-2">
          {{ $t('metrics.table.details') }}
        </summary>
        <dl class="space-y-1" data-slot="tenant-rep-metrics-periods">
          <div v-for="period in periods" :key="period.key">
            <dt class="inline font-medium">
              {{ period.label }}:
            </dt>
            <dd class="inline">
              {{ period.value ? `${period.value.label} · ${formatDate(period.value.from)} – ${formatDate(period.value.until)}` : $t('metrics.baseline.unavailable') }}
            </dd>
          </div>
        </dl>
        <p>{{ $t('metrics.table.recording_definition') }}</p>
        <p>{{ $t('metrics.table.agenda_definition') }}</p>
        <p>{{ $t('metrics.table.task_definition') }}</p>
        <p v-if="data.trendSince" data-slot="tenant-rep-metrics-trend">
          {{ $t('metrics.table.trend_definition', { days: 14, date: formatDate(data.trendSince) }) }}
        </p>
        <p data-slot="tenant-rep-metrics-baseline">
          {{ $t('metrics.table.retrospective') }}
        </p>
      </details>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { TenantRepMetrics } from '../types';

import RepMetricTable from '@/Components/Overview/RepMetricTable.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{ data: TenantRepMetrics | null; loading: boolean }>();
const compactPeriod = (label?: string) => label?.replace(/(\d{4})–(\d{4})/, (_, start: string, end: string) => `${start}–${end.slice(2)}`) ?? '—';
const periods = computed(() => [
  { key: 'current', label: $t('metrics.table.current_cadence'), value: props.data?.currentPeriod },
  { key: 'baseline', label: $t('metrics.table.comparison_cadence'), value: props.data?.baselinePeriod },
]);
</script>
