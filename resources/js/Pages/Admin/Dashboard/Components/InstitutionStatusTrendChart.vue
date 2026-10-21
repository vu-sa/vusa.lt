<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <p class="text-sm text-muted-foreground">
        {{ $t('visak.institution_summary.trend_description') }}
      </p>
      <div class="flex gap-1 border border-border bg-background p-1 text-xs font-medium">
        <button
          v-for="option in rangeOptions"
          :key="option"
          type="button"
          :data-testid="`trend-range-${option}`"
          class="px-2.5 py-1 transition-colors pointer-coarse:min-h-11 pointer-coarse:px-4"
          :class="days === option ? 'bg-foreground text-background' : 'text-muted-foreground hover:bg-accent'"
          :aria-pressed="days === option"
          @click="$emit('update:days', option)"
        >
          {{ $t('visak.institution_summary.trend_range_days', { days: option }) }}
        </button>
      </div>
    </div>

    <div v-if="loading && data.length === 0" class="flex h-64 flex-col justify-between border border-border bg-card p-4" data-slot="institution-trend-skeleton" aria-hidden="true">
      <div class="flex flex-1 items-end border-b border-border pb-3">
        <Skeleton
          class="h-3/4 w-full"
          :style="{ clipPath: skeletonAreaClipPath }"
        />
      </div>
      <div class="flex justify-between gap-4 pt-3">
        <Skeleton v-for="tick in 4" :key="tick" class="h-3 w-10" />
      </div>
    </div>
    <div
      v-else-if="data.length === 0"
      class="flex h-64 items-center justify-center border border-border text-sm text-muted-foreground"
    >
      {{ $t('visak.institution_summary.trend_empty') }}
    </div>
    <div v-else ref="wrapper" class="relative h-64 w-full border border-border bg-card" data-slot="institution-trend-plot" :aria-busy="loading">
      <svg ref="svgRef" data-testid="trend-chart-svg" class="size-full" />

      <Teleport to="body">
        <div
          v-if="tooltip.visible"
          ref="tooltipElement"
          role="status"
          data-slot="trend-tooltip"
          class="pointer-events-none fixed z-50 w-max max-w-[calc(100vw-1rem)] min-w-40 border border-border bg-popover px-3 py-2 text-xs text-popover-foreground"
          :style="{ left: `${tooltip.x}px`, top: `${tooltip.y}px` }"
        >
          <p class="mb-1 font-semibold">
            {{ tooltip.date }}
          </p>
          <p class="mb-1 tabular-nums">
            {{ $t('metrics.history.total', { count: tooltip.total }) }}
          </p>
          <ul class="space-y-0.5">
            <li v-for="entry in tooltip.entries" :key="entry.key" class="flex items-center justify-between gap-3">
              <span class="flex items-center gap-1.5">
                <span class="inline-block size-2" :class="entry.swatchClass" />
                {{ $t(entry.labelKey) }}
              </span>
              <span class="tabular-nums">
                {{ entry.value }}
              </span>
            </li>
          </ul>
          <p v-if="tooltip.flow.length > 0" class="mt-1.5 border-t border-border pt-1.5 font-medium" data-slot="trend-tooltip-flow-period">
            {{ $t('metrics.history.flow_period', { from: tooltip.flowFrom, until: tooltip.flowUntil }) }}
          </p>
          <ul v-if="tooltip.flow.length > 0" class="mt-1 space-y-0.5" data-slot="trend-tooltip-flow">
            <li v-for="entry in tooltip.flow" :key="entry.labelKey" class="flex items-center justify-between gap-3">
              <span>
                {{ $t(entry.labelKey) }}
              </span>
              <span class="tabular-nums">
                {{ entry.value }}
              </span>
            </li>
          </ul>
          <p v-if="tooltip.reconstructed" class="mt-1.5 text-muted-foreground">
            {{ $t('visak.institution_summary.trend_reconstructed') }}
          </p>
        </div>
      </Teleport>
    </div>

    <p v-if="data.some(point => point.reconstructed)" class="text-xs text-muted-foreground" data-slot="trend-reconstruction-legend">
      {{ $t('metrics.history.reconstructed') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import {
  area,
  axisBottom,
  axisLeft,
  bisector,
  type CurveFactory,
  curveLinear,
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

import type { InstitutionStatusHistoryPoint } from '../types';
import { groupStatusFlows, type StatusFlowBucket } from '../Composables/statusFlow';

import { Skeleton } from '@/Components/ui/skeleton';

type StatusKey = 'current' | 'recent_check_in' | 'approaching' | 'overdue' | 'no_activity';

const props = withDefaults(defineProps<{
  data: InstitutionStatusHistoryPoint[];
  days: number;
  loading?: boolean;
  /** The comparison cadency ends on this date; shown only inside the visible range. */
  baselineDate?: string | null;
  missingDates?: string[];
}>(), { missingDates: () => [], baselineDate: null, loading: false });

defineEmits<{
  'update:days': [days: number];
}>();

const rangeOptions = [30, 90, 180] as const;
const skeletonAreaClipPath = 'polygon(0 55%, 12% 48%, 24% 58%, 36% 35%, 48% 42%, 60% 28%, 72% 40%, 84% 20%, 100% 32%, 100% 100%, 0 100%)';

// Bottom-to-top: healthy baseline first, escalating severity stacked above it. The series are
// statuses, so they use the status roles, not the categorical `--cat-*` hues (visual.md).
const seriesConfig: Array<{ key: StatusKey; labelKey: string; fillClass: string; swatchClass: string }> = [
  { key: 'current', labelKey: 'visak.institution_summary.current', fillClass: 'fill-[color-mix(in_oklab,var(--status-success)_70%,var(--status-success-surface))]', swatchClass: 'bg-[color-mix(in_oklab,var(--status-success)_70%,var(--status-success-surface))]' },
  { key: 'recent_check_in', labelKey: 'activity_requests.recent_check_in', fillClass: 'fill-[color-mix(in_oklab,var(--status-info)_70%,var(--status-info-surface))]', swatchClass: 'bg-[color-mix(in_oklab,var(--status-info)_70%,var(--status-info-surface))]' },
  { key: 'approaching', labelKey: 'visak.institution_summary.approaching', fillClass: 'fill-[color-mix(in_oklab,var(--status-attention)_70%,var(--status-attention-surface))]', swatchClass: 'bg-[color-mix(in_oklab,var(--status-attention)_70%,var(--status-attention-surface))]' },
  { key: 'overdue', labelKey: 'visak.institution_summary.overdue', fillClass: 'fill-[color-mix(in_oklab,var(--status-danger)_70%,var(--status-danger-surface))]', swatchClass: 'bg-[color-mix(in_oklab,var(--status-danger)_70%,var(--status-danger-surface))]' },
  { key: 'no_activity', labelKey: 'visak.institution_summary.no_activity', fillClass: 'fill-[color-mix(in_oklab,var(--status-neutral)_70%,var(--status-neutral-surface))]', swatchClass: 'bg-[color-mix(in_oklab,var(--status-neutral)_70%,var(--status-neutral-surface))]' },
];

const hasFlow = computed(() => props.data.some(point => point.entered_attention !== undefined || point.left_attention !== undefined));

const FLOW_STRIP_HEIGHT = 40;
const FLOW_STRIP_GAP = 10;

const wrapper = ref<HTMLDivElement | null>(null);
const tooltipElement = ref<HTMLDivElement | null>(null);
const svgRef = ref<SVGSVGElement | null>(null);
let resizeObserver: ResizeObserver | null = null;

const tooltip = reactive<{
  visible: boolean;
  x: number;
  y: number;
  date: string;
  entries: Array<{ key: StatusKey; labelKey: string; swatchClass: string; value: number }>;
  flow: Array<{ labelKey: string; value: number }>;
  flowFrom: string;
  flowUntil: string;
  reconstructed: boolean;
  total: number;
}>({ visible: false, x: 0, y: 0, date: '', entries: [], flow: [], flowFrom: '', flowUntil: '', reconstructed: false, total: 0 });

function flowEntries(point: StatusFlowBucket): Array<{ labelKey: string; value: number }> {
  return [
    { labelKey: 'visak.institution_summary.trend_flow_meetings', value: point.meetings_registered ?? 0 },
    { labelKey: 'visak.institution_summary.trend_flow_check_ins', value: point.check_ins_registered ?? 0 },
    { labelKey: 'visak.institution_summary.trend_flow_left', value: point.left_attention ?? 0 },
    { labelKey: 'visak.institution_summary.trend_flow_entered', value: point.entered_attention ?? 0 },
  ].filter(entry => entry.value > 0);
}

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
  const margin = { top: 36, right: 12, bottom: 24, left: 36 };

  const svg = select(svgRef.value);
  svg.selectAll('*').remove();
  tooltip.visible = false;

  svg.attr('viewBox', `0 0 ${width} ${height}`).attr('preserveAspectRatio', 'none');

  const points: (InstitutionStatusHistoryPoint & { missing: boolean })[] = [
    ...props.data.map(point => ({ ...point, missing: false })),
    ...props.missingDates.map(date => ({ date, all: 0, current: 0, recent_check_in: 0, approaching: 0, overdue: 0, no_activity: 0, needs_attention: 0, missing: true })),
  ].sort((a, b) => a.date.localeCompare(b.date));
  const xScale = scaleTime()
    .domain([parseDate(points[0].date), parseDate(points[points.length - 1].date)])
    .range([margin.left, width - margin.right]);

  const yMax = d3max(points, d => d.all) ?? 0;
  const stripHeight = hasFlow.value ? FLOW_STRIP_HEIGHT : 0;
  const areaBottom = height - margin.bottom - (hasFlow.value ? stripHeight + FLOW_STRIP_GAP : 0);
  const yScale = scaleLinear()
    .domain([0, yMax])
    .nice()
    .range([areaBottom, margin.top]);

  const stackedSeries = stack<InstitutionStatusHistoryPoint, StatusKey>()
    .keys(seriesConfig.map(series => series.key))(points);

  const areaGenerator = area<[number, number] & { data: InstitutionStatusHistoryPoint }>()
    .defined(d => !props.missingDates.includes(d.data.date))
    .x(d => xScale(parseDate(d.data.date)))
    .y0(d => yScale(d[0]))
    .y1(d => yScale(d[1]))
    .curve(curveLinear as CurveFactory);

  svg
    .append('g')
    .selectAll('path')
    .data(stackedSeries)
    .join('path')
    .attr('class', (_, index) => seriesConfig[index].fillClass)
    .attr('d', series => areaGenerator(series as unknown as Array<[number, number] & { data: InstitutionStatusHistoryPoint }>));

  const flowPoints = groupStatusFlows(props.data, points[0].date, points[points.length - 1].date, props.days, width - margin.left - margin.right);
  if (hasFlow.value) {
    const midline = height - margin.bottom - stripHeight / 2;
    const flowMax = Math.max(1, d3max(flowPoints, d => Math.max(d.left_attention ?? 0, d.entered_attention ?? 0)) ?? 1);
    const half = stripHeight / 2;
    const barWidth = 4;
    const bar = (className: string, value: (point: StatusFlowBucket) => number, direction: 1 | -1) => svg
      .append('g')
      .selectAll('rect')
      .data(flowPoints.filter(point => value(point) > 0))
      .join('rect')
      .attr('class', className)
      .attr('data-count', d => value(d))
      .attr('data-direction', direction)
      .attr('data-from', d => d.from)
      .attr('data-until', d => d.until)
      .attr('x', (d) => {
        const center = (xScale(parseDate(d.from)) + xScale(parseDate(d.until))) / 2;
        return Math.max(margin.left, Math.min(width - margin.right - barWidth, center - barWidth / 2));
      })
      .attr('width', barWidth)
      .attr('height', d => (value(d) / flowMax) * half)
      .attr('y', d => (direction === 1 ? midline - (value(d) / flowMax) * half : midline));

    bar('fill-status-success', d => d.left_attention ?? 0, 1);
    bar('fill-status-danger', d => d.entered_attention ?? 0, -1);

    svg
      .append('line')
      .attr('class', 'stroke-border')
      .attr('x1', margin.left)
      .attr('x2', width - margin.right)
      .attr('y1', midline)
      .attr('y2', midline);
  }

  const baselineX = props.baselineDate ? xScale(parseDate(props.baselineDate)) : null;
  if (baselineX !== null && baselineX >= margin.left && baselineX <= width - margin.right) {
    svg
      .append('line')
      .attr('data-testid', 'trend-baseline-marker')
      .attr('class', 'stroke-foreground/60')
      .attr('stroke-dasharray', '3 3')
      .attr('x1', baselineX)
      .attr('x2', baselineX)
      .attr('y1', margin.top)
      .attr('y2', height - margin.bottom);
    const label = svg
      .append('text')
      .attr('class', 'fill-muted-foreground text-[11px]')
      .attr('x', baselineX + 4)
      .attr('y', margin.top - 12)
      .text($t('metrics.history.baseline_end'));
    const labelWidth = label.node()?.getComputedTextLength?.() ?? $t('metrics.history.baseline_end').length * 6;
    const fitsRight = baselineX + 4 + labelWidth <= width - margin.right;
    label.attr('text-anchor', fitsRight ? 'start' : 'end').attr('x', baselineX + (fitsRight ? 4 : -4));
  }

  const yAxis = svg.append('g').attr('transform', `translate(${margin.left},0)`)
    .attr('data-testid', 'trend-count-axis').call(axisLeft(yScale).ticks(4).tickFormat(value => `${value}`));
  yAxis.select('.domain').attr('class', 'stroke-border');
  yAxis.selectAll('.tick line').attr('class', 'stroke-border');
  yAxis.selectAll('.tick text').attr('class', 'fill-muted-foreground text-[11px]');

  const xAxis: Selection<SVGGElement, unknown, null, undefined> = svg
    .append('g')
    .attr('transform', `translate(0,${height - margin.bottom})`)
    .call(axisBottom(xScale).ticks(Math.min(6, points.length)).tickFormat(timeFormat('%b %d') as (domainValue: Date | number | { valueOf(): number }) => string));

  xAxis.select('.domain').attr('class', 'stroke-border');
  xAxis.selectAll('.tick line').attr('class', 'stroke-border');
  xAxis.selectAll('.tick text').attr('class', 'fill-muted-foreground text-[11px]');

  const bisectDate = bisector<InstitutionStatusHistoryPoint, Date>(d => parseDate(d.date)).center;
  const availableIndices = points.flatMap((point, index) => point.missing ? [] : [index]);
  let selectedIndex = availableIndices[0] ?? 0;
  function showPoint(index: number, x?: number, y?: number): void {
    const point = points[index];
    if (!point || point.missing) {
      tooltip.visible = false;
      return;
    }
    tooltip.date = formatTooltipDate(point.date);
    tooltip.entries = seriesConfig.map(series => ({ key: series.key, labelKey: series.labelKey, swatchClass: series.swatchClass, value: point[series.key] }));
    tooltip.total = point.all;
    const flow = flowPoints.find(bucket => bucket.from <= point.date && bucket.until >= point.date);
    tooltip.flow = flow ? flowEntries(flow) : [];
    tooltip.flowFrom = flow?.from ?? '';
    tooltip.flowUntil = flow?.until ?? '';
    tooltip.reconstructed = point.reconstructed === true;
    const bounds = svgRef.value!.getBoundingClientRect();
    const anchorX = x ?? bounds.left + xScale(parseDate(point.date)) * bounds.width / width;
    const anchorY = y ?? bounds.top + margin.top;
    tooltip.x = anchorX;
    tooltip.y = anchorY;
    tooltip.visible = true;
    void nextTick(() => {
      const box = tooltipElement.value?.getBoundingClientRect();
      if (!box) return;
      tooltip.x = Math.max(8, Math.min(window.innerWidth - box.width - 8, anchorX));
      tooltip.y = Math.max(8, Math.min(window.innerHeight - box.height - 8, anchorY));
    });
    selectedIndex = index;
  }
  svg.append('rect')
    .attr('x', margin.left).attr('y', margin.top)
    .attr('width', Math.max(0, width - margin.left - margin.right))
    .attr('height', Math.max(0, height - margin.top - margin.bottom))
    .attr('fill', 'transparent').attr('tabindex', 0)
    .attr('role', 'group').attr('aria-label', $t('metrics.history.inspect'))
    .attr('data-testid', 'trend-inspector')
    .on('pointermove', (event: PointerEvent) => {
      const [x] = pointer(event);
      showPoint(bisectDate(points, xScale.invert(x)), event.clientX + 12, event.clientY + 12);
    })
    .on('pointerdown', (event: PointerEvent) => {
      const [x] = pointer(event);
      showPoint(bisectDate(points, xScale.invert(x)), event.clientX + 12, event.clientY + 12);
    })
    .on('focus', () => showPoint(selectedIndex))
    .on('keydown', (event: KeyboardEvent) => {
      if (event.key === 'Escape') tooltip.visible = false;
      if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
      event.preventDefault();
      const next = availableIndices.indexOf(selectedIndex) + (event.key === 'ArrowLeft' ? -1 : 1);
      showPoint(availableIndices[Math.max(0, Math.min(availableIndices.length - 1, next))]);
    })
    .on('blur', () => { tooltip.visible = false; })
    .on('pointerleave', (event: PointerEvent) => { if (event.pointerType === 'mouse') hideTooltip(); });
}

function hideTooltip(): void {
  tooltip.visible = false;
}

function dismissOutside(event: PointerEvent): void {
  if (event.target instanceof Node && !svgRef.value?.contains(event.target)) hideTooltip();
}

onMounted(() => {
  window.addEventListener('scroll', hideTooltip, true);
  window.addEventListener('resize', hideTooltip);
  document.addEventListener('pointerdown', dismissOutside);
  render();
});

watch(wrapper, (element) => {
  resizeObserver?.disconnect();
  resizeObserver = element ? new ResizeObserver(() => render()) : null;
  if (element && resizeObserver) {
    resizeObserver.observe(element);
  }
});

watch(() => [props.data, props.days, props.baselineDate, props.missingDates], render, { deep: true, flush: 'post' });

onBeforeUnmount(() => {
  window.removeEventListener('scroll', hideTooltip, true);
  window.removeEventListener('resize', hideTooltip);
  document.removeEventListener('pointerdown', dismissOutside);
  resizeObserver?.disconnect();
  resizeObserver = null;
});
</script>
