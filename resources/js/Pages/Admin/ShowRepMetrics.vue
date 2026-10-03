<template>
  <OverviewPage
    :eyebrow="$t('shell.workspaces.sistema.title')"
    :title="$t('metrics.title')"
    :head-title="`${$t('shell.workspaces.sistema.title')} · ${$t('metrics.title')}`"
    :lead="$t('metrics.lead')"
  >
    <Deferred data="report">
      <template #fallback>
        <div class="flex flex-col gap-4" data-slot="metrics-loading">
          <Skeleton class="h-10 w-full" />
          <Skeleton class="h-10 w-full" />
          <Skeleton class="h-48 w-full" />
        </div>
      </template>

      <template v-if="report">
        <OverviewSection :title="$t('metrics.table.title')">
          <div class="overflow-x-auto border-y border-border">
            <table class="w-full min-w-[32rem] text-sm" data-testid="metrics-table">
              <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  <th scope="col" class="py-2 pr-4 font-semibold">
                    {{ $t('metrics.table.metric') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.table.now') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.table.baseline') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.table.target') }}
                  </th>
                  <th scope="col" class="py-2 pl-4 font-semibold">
                    {{ $t('metrics.table.status') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="metric in report.metrics" :key="metric.key" :data-metric="metric.key">
                  <th scope="row" class="py-3 pr-4 text-left font-medium">
                    {{ $t(`metrics.metrics.${metric.key}`) }}
                    <span v-if="metric.trend === null" class="block text-xs font-normal text-muted-foreground">
                      {{ $t('metrics.table.snapshot') }}
                    </span>
                  </th>
                  <td class="px-4 py-3 text-right font-semibold tabular-nums">
                    {{ percent(metric.now) }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">
                    {{ percent(metric.baseline) }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums text-muted-foreground">
                    {{ percent(metric.target) }}
                  </td>
                  <td :class="['py-3 pl-4 font-medium', statusClass(metric)]" data-slot="metric-status">
                    {{ $t(`metrics.status.${statusOf(metric)}`) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </OverviewSection>

        <OverviewSection :title="$t('metrics.chart.title')">
          <div class="flex flex-col gap-3">
            <OverviewScopeSwitch
              v-model="chartMetric"
              :options="chartOptions"
              :label="$t('metrics.chart.label')"
              class="self-start"
            />
            <OverviewChart :summary="chartSummary">
              <MetricTrendChart :points="chartPoints" :target="chartTarget" />
            </OverviewChart>
          </div>
        </OverviewSection>

        <OverviewSection :title="$t('metrics.tasks.title')" :empty="report.tasks.length === 0" :empty-text="$t('metrics.tasks.empty')">
          <div class="overflow-x-auto border-y border-border">
            <table class="w-full min-w-[28rem] text-sm" data-testid="task-table">
              <thead>
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                  <th scope="col" class="py-2 pr-4 font-semibold">
                    {{ $t('metrics.tasks.type') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.tasks.total') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.tasks.completed') }}
                  </th>
                  <th scope="col" class="px-4 py-2 text-right font-semibold">
                    {{ $t('metrics.tasks.rate') }}
                  </th>
                  <th scope="col" class="py-2 pl-4 text-right font-semibold">
                    {{ $t('metrics.tasks.median_days') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-border">
                <tr v-for="task in report.tasks" :key="task.actionType" :data-task-type="task.actionType">
                  <th scope="row" class="py-3 pr-4 text-left font-medium">
                    {{ $t(`metrics.task_types.${task.actionType}`) }}
                  </th>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ task.total }}
                  </td>
                  <td class="px-4 py-3 text-right tabular-nums">
                    {{ task.completed }}
                  </td>
                  <td class="px-4 py-3 text-right font-semibold tabular-nums">
                    {{ percent(task.rate) }}
                  </td>
                  <td class="py-3 pl-4 text-right tabular-nums text-muted-foreground">
                    {{ task.medianDays ?? '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </OverviewSection>
      </template>
    </Deferred>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Deferred } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import MetricTrendChart from '@/Components/Overview/MetricTrendChart.vue';
import OverviewChart from '@/Components/Overview/OverviewChart.vue';
import OverviewScopeSwitch from '@/Components/Overview/OverviewScopeSwitch.vue';
import { summarizeMetricTrend, type MetricPoint } from '@/Components/Overview/metricTrend';
import { OverviewSection } from '@/Components/Patterns';
import { Skeleton } from '@/Components/ui/skeleton';

interface Metric {
  key: string;
  now: number | null;
  baseline: number | null;
  target: number | null;
  /** Null for a snapshot: `users.last_action` keeps no history. */
  trend: MetricPoint[] | null;
}

interface RepMetricsReport {
  months: string[];
  metrics: Metric[];
  tasks: { actionType: string; total: number; completed: number; rate: number | null; medianDays: number | null }[];
}

const props = defineProps<{
  report?: RepMetricsReport;
}>();

const percent = (value: number | null) => (value === null ? '—' : `${value} %`);

type MetricStatus = 'no_data' | 'reached' | 'improved' | 'behind' | 'measured';

// Higher is better for every metric here. Colour is never the only signal: each status has a word.
function statusOf(metric: Metric): MetricStatus {
  if (metric.now === null) {
    return 'no_data';
  }

  if (metric.target === null || metric.baseline === null) {
    return 'measured';
  }

  if (metric.now >= metric.target) {
    return 'reached';
  }

  return metric.now >= metric.baseline ? 'improved' : 'behind';
}

function statusClass(metric: Metric): string {
  return {
    no_data: 'text-status-neutral',
    measured: 'text-status-neutral',
    reached: 'text-status-success',
    improved: 'text-status-progress',
    behind: 'text-status-attention',
  }[statusOf(metric)];
}

const trendMetrics = computed(() => (props.report?.metrics ?? []).filter(metric => metric.trend !== null));
const chartMetric = ref('periodicity_gap');

const chartOptions = computed(() => trendMetrics.value.map(metric => ({
  value: metric.key,
  label: $t(`metrics.chart_labels.${metric.key}`),
})));

const selected = computed(() => trendMetrics.value.find(metric => metric.key === chartMetric.value) ?? trendMetrics.value[0]);
const chartPoints = computed<MetricPoint[]>(() => selected.value?.trend ?? []);
const chartTarget = computed(() => selected.value?.target ?? null);

// The figure's text twin (O17): the plot alone must never be the only carrier of the message.
const chartSummary = computed(() => {
  const trend = summarizeMetricTrend(chartPoints.value);

  if (!selected.value || !trend) {
    return $t('metrics.chart.empty');
  }

  const sentence = $t('metrics.chart.summary', {
    metric: $t(`metrics.metrics.${selected.value.key}`),
    last: String(trend.last.value),
    month: trend.last.month,
    first: String(trend.first.value),
    firstMonth: trend.first.month,
  });
  const target = selected.value.target === null ? '' : ` ${$t('metrics.chart.target', { target: String(selected.value.target) })}`;

  return `${sentence} ${$t(`metrics.chart.${trend.direction}`)}${target}`;
});
</script>
