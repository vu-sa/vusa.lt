<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <p class="text-sm text-muted-foreground">
        {{ $t('visak.institution_summary.trend_description') }}
      </p>
      <div class="flex gap-1 rounded-lg border bg-background p-1 text-xs font-medium">
        <button
          v-for="option in rangeOptions"
          :key="option"
          type="button"
          :data-testid="`trend-range-${option}`"
          class="rounded-md px-2.5 py-1 transition-colors"
          :class="days === option ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent'"
          @click="$emit('update:days', option)"
        >
          {{ $t('visak.institution_summary.trend_range_days', { days: option }) }}
        </button>
      </div>
    </div>

    <div v-if="loading && data.length === 0" class="flex h-64 items-center justify-center rounded-lg border">
      <Loader2 class="h-6 w-6 animate-spin text-muted-foreground" />
    </div>
    <div
      v-else-if="data.length === 0"
      class="flex h-64 items-center justify-center rounded-lg border text-sm text-muted-foreground"
    >
      {{ $t('visak.institution_summary.trend_empty') }}
    </div>
    <div v-else ref="wrapper" class="relative h-64 w-full rounded-lg border bg-card">
      <svg ref="svgRef" data-testid="trend-chart-svg" class="size-full" />

      <div
        v-if="tooltip.visible"
        class="pointer-events-none absolute z-10 min-w-40 rounded-md border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md"
        :style="{ left: `${tooltip.x}px`, top: `${tooltip.y}px` }"
      >
        <p class="mb-1 font-semibold">
          {{ tooltip.date }}
        </p>
        <ul class="space-y-0.5">
          <li v-for="entry in tooltip.entries" :key="entry.key" class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-1.5">
              <span class="inline-block h-2 w-2 rounded-sm" :class="entry.swatchClass" />
              {{ $t(entry.labelKey) }}
            </span>
            <span class="tabular-nums">{{ entry.value }}</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="flex flex-wrap gap-3 text-xs text-muted-foreground">
      <span v-for="series in seriesConfig" :key="series.key" class="flex items-center gap-1.5">
        <span class="inline-block h-2.5 w-2.5 rounded-sm" :class="series.swatchClass" />
        {{ $t(series.labelKey) }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
  area,
  axisBottom,
  bisector,
  type CurveFactory,
  curveMonotoneX,
  max as d3max,
  pointer,
  scaleLinear,
  scaleTime,
  select,
  type Selection,
  stack,
  timeFormat,
} from 'd3';
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';

import type { InstitutionStatusHistoryPoint } from '../types';

type StatusKey = 'current' | 'approaching' | 'overdue' | 'no_activity';

const props = defineProps<{
  data: InstitutionStatusHistoryPoint[];
  days: number;
  loading?: boolean;
}>();

defineEmits<{
  'update:days': [days: number];
}>();

const rangeOptions = [30, 90, 180] as const;

// Bottom-to-top: healthy baseline first, escalating severity stacked above it.
const seriesConfig: Array<{ key: StatusKey; labelKey: string; fillClass: string; swatchClass: string }> = [
  { key: 'current', labelKey: 'visak.institution_summary.current', fillClass: 'fill-emerald-400/80 dark:fill-emerald-500/60', swatchClass: 'bg-emerald-400 dark:bg-emerald-500' },
  { key: 'approaching', labelKey: 'visak.institution_summary.approaching', fillClass: 'fill-amber-400/80 dark:fill-amber-500/60', swatchClass: 'bg-amber-400 dark:bg-amber-500' },
  { key: 'overdue', labelKey: 'visak.institution_summary.overdue', fillClass: 'fill-orange-400/80 dark:fill-orange-500/60', swatchClass: 'bg-orange-400 dark:bg-orange-500' },
  { key: 'no_activity', labelKey: 'visak.institution_summary.no_activity', fillClass: 'fill-zinc-400/70 dark:fill-zinc-600/70', swatchClass: 'bg-zinc-400 dark:bg-zinc-600' },
];

const wrapper = ref<HTMLDivElement | null>(null);
const svgRef = ref<SVGSVGElement | null>(null);
let resizeObserver: ResizeObserver | null = null;

const tooltip = reactive<{
  visible: boolean;
  x: number;
  y: number;
  date: string;
  entries: Array<{ key: StatusKey; labelKey: string; swatchClass: string; value: number }>;
}>({ visible: false, x: 0, y: 0, date: '', entries: [] });

function parseDate(value: string): Date {
  return new Date(`${value}T00:00:00`);
}

function formatTooltipDate(value: string): string {
  return timeFormat('%Y-%m-%d')(parseDate(value));
}

function render(): void {
  if (!svgRef.value || !wrapper.value || props.data.length === 0) {
    return;
  }

  const width = wrapper.value.clientWidth || 600;
  const height = wrapper.value.clientHeight || 256;
  const margin = { top: 12, right: 12, bottom: 24, left: 32 };

  const svg = select(svgRef.value);
  svg.selectAll('*').remove();
  tooltip.visible = false;

  svg.attr('viewBox', `0 0 ${width} ${height}`).attr('preserveAspectRatio', 'none');

  const points = props.data;
  const xScale = scaleTime()
    .domain([parseDate(points[0].date), parseDate(points[points.length - 1].date)])
    .range([margin.left, width - margin.right]);

  const yMax = d3max(points, d => d.all) ?? 0;
  const yScale = scaleLinear()
    .domain([0, yMax])
    .nice()
    .range([height - margin.bottom, margin.top]);

  const stackedSeries = stack<InstitutionStatusHistoryPoint, StatusKey>()
    .keys(seriesConfig.map(series => series.key))(points);

  const areaGenerator = area<[number, number] & { data: InstitutionStatusHistoryPoint }>()
    .x(d => xScale(parseDate(d.data.date)))
    .y0(d => yScale(d[0]))
    .y1(d => yScale(d[1]))
    .curve(curveMonotoneX as CurveFactory);

  svg
    .append('g')
    .selectAll('path')
    .data(stackedSeries)
    .join('path')
    .attr('class', (_, index) => seriesConfig[index].fillClass)
    .attr('d', series => areaGenerator(series as unknown as Array<[number, number] & { data: InstitutionStatusHistoryPoint }>));

  const xAxis: Selection<SVGGElement, unknown, null, undefined> = svg
    .append('g')
    .attr('transform', `translate(0,${height - margin.bottom})`)
    .call(axisBottom(xScale).ticks(Math.min(6, points.length)).tickFormat(timeFormat('%b %d') as (domainValue: Date | number | { valueOf(): number }) => string));

  xAxis.select('.domain').attr('class', 'stroke-border');
  xAxis.selectAll('.tick line').attr('class', 'stroke-border');
  xAxis.selectAll('.tick text').attr('class', 'fill-muted-foreground text-[11px]');

  const bisectDate = bisector<InstitutionStatusHistoryPoint, Date>(d => parseDate(d.date)).left;

  svg
    .append('rect')
    .attr('x', margin.left)
    .attr('y', margin.top)
    .attr('width', Math.max(0, width - margin.left - margin.right))
    .attr('height', Math.max(0, height - margin.top - margin.bottom))
    .attr('fill', 'transparent')
    .style('cursor', 'crosshair')
    .on('mousemove', (event: MouseEvent) => {
      const [mouseX] = pointer(event);
      const targetDate = xScale.invert(mouseX);
      const index = Math.min(points.length - 1, Math.max(0, bisectDate(points, targetDate)));
      const point = points[index];
      const bounds = wrapper.value?.getBoundingClientRect();
      if (!bounds || !point) {
        return;
      }

      tooltip.date = formatTooltipDate(point.date);
      tooltip.entries = seriesConfig.map(series => ({
        key: series.key,
        labelKey: series.labelKey,
        swatchClass: series.swatchClass,
        value: point[series.key],
      }));
      tooltip.x = event.clientX - bounds.left + 12;
      tooltip.y = event.clientY - bounds.top + 12;
      tooltip.visible = true;
    })
    .on('mouseleave', () => {
      tooltip.visible = false;
    });
}

onMounted(() => {
  render();
});

// The wrapper/<svg> only enter the DOM once data.length > 0 (see template
// v-else) — on first mount (still loading, data=[]) the ref is null, so the
// observer couldn't be attached in onMounted. Attaching it here instead means
// it's (re)wired whenever the element actually appears, including the first
// time data arrives on an already-mounted instance.
watch(wrapper, (element) => {
  resizeObserver?.disconnect();
  resizeObserver = element ? new ResizeObserver(() => render()) : null;
  if (element && resizeObserver) {
    resizeObserver.observe(element);
  }
});

// Same DOM-timing issue for the initial render: on the very first arrival of
// data the <svg> ref isn't bound yet when this watcher's default (pre-flush)
// callback runs — awaiting a tick lets Vue patch the DOM first.
watch(() => props.data, async () => {
  await nextTick();
  render();
}, { deep: true });

onBeforeUnmount(() => {
  resizeObserver?.disconnect();
  resizeObserver = null;
});
</script>
