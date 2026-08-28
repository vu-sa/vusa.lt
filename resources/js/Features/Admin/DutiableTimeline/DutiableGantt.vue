<template>
  <!--
    Fills the space the editor gives it, but never more than its own content needs: with
    four rows the box ends after the fourth lane and the dock rises to meet it, with forty
    it stops at the available height and scrolls inside.
  -->
  <div
    data-slot="dutiable-gantt"
    class="flex min-h-0 flex-auto overflow-hidden rounded-md border border-border"
    :style="{ maxHeight: `${HEADER_HEIGHT + totalHeight + 2}px` }"
  >
    <!-- Label column. Scrolls vertically in lockstep with the chart, never horizontally. -->
    <div
      class="relative flex shrink-0 flex-col border-r border-border bg-card"
      :style="{ width: `${labelWidth}px` }"
    >
      <div class="shrink-0 border-b border-border bg-muted/40" :style="{ height: `${HEADER_HEIGHT}px` }" />
      <div ref="labelScroller" class="min-h-0 flex-1 overflow-hidden">
        <div :style="{ height: `${totalHeight}px`, transform: `translateY(${-scrollTop}px)` }">
          <template v-for="lane in layoutRows" :key="lane.key">
            <!-- The chevron and the name are separate targets: collapsing a group and
                 opening the record it names are different intentions. -->
            <div
              v-if="lane.type === 'tenant'"
              class="flex w-full items-center gap-1 px-2 text-xs font-semibold"
              :style="{ height: `${lane.height}px` }"
            >
              <button
                type="button"
                class="shrink-0 rounded p-0.5 hover:bg-accent"
                :aria-label="$t('dutiables.timeline.collapse_all')"
                @click="emit('toggle-group', lane.key)"
              >
                <ChevronRight class="size-3 transition-transform" :class="{ 'rotate-90': !collapsed.has(lane.key) }" />
              </button>
              <Checkbox
                :model-value="groupSelection(lane.key)"
                class="size-3.5 shrink-0"
                :aria-label="$t('dutiables.timeline.select_group')"
                @update:model-value="emit('toggle-group-selection', lane.key)"
              />
              <Link
                v-if="groupHref(lane)"
                :href="groupHref(lane)!"
                class="truncate hover:underline"
                :title="lane.group.label"
              >
                {{ lane.group.label }}
              </Link>
              <span v-else class="truncate" :title="lane.group.label">{{ lane.group.label }}</span>
              <span
                v-if="lane.group.sublabel"
                class="truncate text-[10px] font-normal text-muted-foreground"
                :title="lane.group.sublabel"
              >
                {{ lane.group.sublabel }}
              </span>
            </div>
            <div
              v-else
              class="flex items-center gap-1.5 truncate px-2 pl-4 text-[11px] text-muted-foreground"
              :class="{ 'bg-accent/60': lane.row && selectedIds.has(lane.row.id) }"
              :style="{ height: `${lane.height}px` }"
            >
              <Checkbox
                :model-value="lane.row ? selectedIds.has(lane.row.id) : false"
                class="size-3.5 shrink-0"
                :aria-label="laneLabel(lane)"
                @update:model-value="lane.row && emit('toggle-selection', lane.row.id)"
              />
              <Link2 v-if="lane.row?.is_derived" class="size-3 shrink-0 opacity-60" />
              <Link
                v-if="laneHref(lane)"
                :href="laneHref(lane)!"
                class="truncate hover:text-foreground hover:underline"
                :title="laneLabel(lane)"
              >
                {{ laneLabel(lane) }}
              </Link>
              <span v-else class="truncate" :title="laneLabel(lane)">{{ laneLabel(lane) }}</span>
              <DutiableExtrasBadge v-if="lane.row?.extras" :extras="lane.row.extras" />
            </div>
          </template>
        </div>
      </div>

      <!-- Drag handle for the label column width. -->
      <div
        class="absolute -right-1 top-0 z-10 h-full w-2 cursor-col-resize"
        @pointerdown="startResize"
      />
    </div>

    <!-- Chart. Horizontal scroll lives here; the header is a separate sticky SVG. -->
    <div class="flex min-w-0 flex-1 flex-col">
      <div ref="headerScroller" class="shrink-0 overflow-hidden border-b border-border">
        <svg ref="headerSvg" :height="HEADER_HEIGHT" :width="chartWidth" />
      </div>
      <div
        ref="scrollContainer"
        class="min-h-0 flex-1 overflow-auto"
        @scroll="onScroll"
      >
        <svg ref="chartSvg" :width="chartWidth" :height="totalHeight" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, shallowRef, watch, nextTick } from 'vue';
import * as d3 from 'd3';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Link2 } from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import { getGanttColors, isDarkModeActive } from '@/Components/Graphs/ganttColors';
import { useColumnResize } from '@/Components/Graphs/composables/useColumnResize';
import { renderBackground } from '@/Components/Graphs/renderers/renderBackground';
import { renderTodayLine } from '@/Components/Graphs/renderers/renderTodayLine';

import { getTimelineColors } from './timelineColors';
import DutiableExtrasBadge from './DutiableExtrasBadge.vue';
import { renderCadenceBands, renderCadenceLabels } from './renderers/renderCadenceBands';
import { renderDragGhost } from './renderers/renderDragGhost';
import { renderDutiableBars } from './renderers/renderDutiableBars';
import { renderMonthGrid, renderMonthHeader } from './renderers/renderMonthGrid';
import { renderTimelineDiagnostics } from './renderers/renderTimelineDiagnostics';
import { useBarDrag, type BarDragState, type DragEntry } from './composables/useBarDrag';
import { toDateString } from './composables/useDutiableTimelineData';
import { TIMELINE_HEADER_HEIGHT as HEADER_HEIGHT, DEFAULT_MONTH_WIDTH } from './constants';
import type { ParsedCadence, ParsedRow, StagedDates, TimelineLayoutRow } from './types';

const props = withDefaults(defineProps<{
  layoutRows: TimelineLayoutRow[];
  cadences: ParsedCadence[];
  /** Terms the cadence filter selected; empty means unfiltered. */
  highlightedCadenceIds?: Set<string>;
  domain: [Date, Date];
  totalHeight: number;
  collapsed: Set<string>;
  rows: ParsedRow[];
  monthWidthPx?: number;
  selectedIds?: Set<string>;
  staged?: Map<string, StagedDates>;
  diagnosticSeverityByRow?: Map<string, 'error' | 'warning' | 'info'>;
  rowTop: (key: string | number) => number;
  rowHeightFor: (key: string | number) => number;
  rowIndex: (key: string | number) => number;
}>(), {
  monthWidthPx: DEFAULT_MONTH_WIDTH,
  highlightedCadenceIds: () => new Set<string>(),
  selectedIds: () => new Set<string>(),
  staged: () => new Map(),
  diagnosticSeverityByRow: () => new Map(),
});

const emit = defineEmits<{
  'toggle-group': [key: string];
  'toggle-selection': [rowId: string];
  'toggle-group-selection': [key: string];
  select: [row: ParsedRow, event: MouseEvent];
  stage: [edits: Array<{ rowId: string; dates: StagedDates }>];
}>();

const scrollContainer = ref<HTMLElement | null>(null);
const headerScroller = ref<HTMLElement | null>(null);
const labelScroller = ref<HTMLElement | null>(null);
const chartSvg = ref<SVGSVGElement | null>(null);
const headerSvg = ref<SVGSVGElement | null>(null);

const labelWidth = ref(220);
const scrollTop = ref(0);
const colors = shallowRef(getGanttColors(isDarkModeActive()));
const timelineColors = shallowRef(getTimelineColors(isDarkModeActive()));

const { startResize } = useColumnResize(
  width => { labelWidth.value = width; },
  () => labelWidth.value,
  { minWidth: 140, maxWidth: 420 },
);

const monthCount = computed(() => {
  const [start, end] = props.domain;

  return Math.max(1, (end.getFullYear() - start.getFullYear()) * 12 + (end.getMonth() - start.getMonth()) + 1);
});

const chartWidth = computed(() => monthCount.value * props.monthWidthPx);

const scale = computed(() => d3.scaleTime().domain(props.domain).range([0, chartWidth.value]));

function laneLabel(lane: TimelineLayoutRow): string {
  // A duty-scoped chart groups by holder, so the lane names the duty, and vice versa.
  return lane.group.kind === 'user'
    ? (lane.row?.duty_name ?? '—')
    : (lane.row?.holder_name ?? '—');
}

/**
 * Tri-state: `'indeterminate'` is what reka-ui's Checkbox wants for a partial group, and
 * it is the only way a header can say "some of these, not all".
 */
function groupSelection(key: string): boolean | 'indeterminate' {
  const rows = props.layoutRows.filter(lane => lane.row && lane.group.key === key);

  if (rows.length === 0) return false;

  const selected = rows.filter(lane => props.selectedIds.has(lane.row!.id)).length;

  if (selected === 0) return false;

  return selected === rows.length ? true : 'indeterminate';
}

/** Whatever the lane is named after — the label and the link must not disagree. */
function laneHref(lane: TimelineLayoutRow): string | null {
  if (!lane.row) return null;

  return lane.group.kind === 'user'
    ? route('duties.show', lane.row.duty_id)
    : route('users.show', lane.row.holder_id);
}

/** A group header names the other side of the same pair. */
function groupHref(lane: TimelineLayoutRow): string | null {
  const id = lane.group.key.split(':')[1];

  if (!id) return null;

  return lane.group.kind === 'user' ? route('users.show', id) : route('duties.show', id);
}

/**
 * Opens on the present rather than on the oldest row in the payload. A ten-year archive
 * otherwise loads scrolled to a term that ended before most of the current holders began.
 */
function scrollToToday(): void {
  const el = scrollContainer.value;

  if (!el) return;

  const [start, end] = props.domain;
  const today = new Date();

  if (today < start || today > end) return;

  el.scrollLeft = Math.max(0, scale.value(today) - el.clientWidth / 2);
  onScroll();
}

/** Header and label column follow the chart's scroll; neither scrolls on its own. */
function onScroll(): void {
  const el = scrollContainer.value;
  if (!el) return;

  scrollTop.value = el.scrollTop;
  if (headerScroller.value) headerScroller.value.scrollLeft = el.scrollLeft;
}

function render(): void {
  if (!chartSvg.value || !headerSvg.value) return;

  const x = scale.value;
  const innerWidth = chartWidth.value;
  const innerHeight = props.totalHeight;

  const svg = d3.select(chartSvg.value);
  svg.selectAll('*').remove();
  const g = svg.append('g');

  renderBackground({
    g,
    x,
    layoutRows: props.layoutRows.map(r => ({ key: r.key, type: r.type, top: r.top, height: r.height })),
    innerWidth,
    innerHeight,
    colors: colors.value,
    minTime: props.domain[0],
    maxTime: props.domain[1],
    rowTop: props.rowTop,
    rowHeightFor: props.rowHeightFor,
    rowIndex: props.rowIndex,
    dayWidthPx: props.monthWidthPx / 30.44,
  });

  renderCadenceBands({
    g,
    x,
    innerHeight,
    colors: colors.value,
    timelineColors: timelineColors.value,
    cadences: props.cadences,
    highlightedIds: props.highlightedCadenceIds,
  });
  renderMonthGrid({
    g, x, innerHeight, colors: colors.value, timelineColors: timelineColors.value, monthWidthPx: props.monthWidthPx,
  });
  renderTodayLine({
    g,
    x,
    innerHeight,
    colors: colors.value,
    minTime: props.domain[0],
    maxTime: props.domain[1],
    showTodayLine: true,
  });

  renderTimelineDiagnostics({
    g,
    timelineColors: timelineColors.value,
    layoutRows: props.layoutRows,
    severityByRow: props.diagnosticSeverityByRow,
  });

  renderDutiableBars({
    g,
    x,
    colors: colors.value,
    timelineColors: timelineColors.value,
    layoutRows: props.layoutRows,
    innerWidth,
    selectedIds: props.selectedIds,
    staged: props.staged,
    onSelect: (row, event) => emit('select', row, event),
  });

  // Appended last so the ghost always sits above the bars, and re-created here because
  // render() clears the SVG — the drag redraws into it without a full render.
  ghostLayer = g.append('g').attr('class', 'drag-ghost').attr('pointer-events', 'none');
  drawGhost(dragState);

  const header = d3.select(headerSvg.value);
  header.selectAll('*').remove();
  const hg = header.append('g');

  renderMonthHeader({ g: hg, x, colors: colors.value, monthWidthPx: props.monthWidthPx, headerHeight: HEADER_HEIGHT });
  renderCadenceLabels({
    g: hg,
    x,
    colors: colors.value,
    cadences: props.cadences,
    highlightedIds: props.highlightedCadenceIds,
    y: 26,
  });
}

/**
 * Zooming without this walks the viewport sideways, because `scrollLeft` is a pixel
 * offset into a canvas that just changed width. Same intent as the meetings chart's
 * `onScaleChange`: keep whatever was under the centre of the viewport there.
 *
 * Worked as a width ratio rather than by inverting the scale, because the watcher already
 * sees the *new* scale — the old one is gone by the time it runs.
 */
watch(() => props.monthWidthPx, (next, previous) => {
  const el = scrollContainer.value;

  if (!el || !previous || previous === next) return;

  const centreOffset = el.clientWidth / 2;
  const fraction = (el.scrollLeft + centreOffset) / (monthCount.value * previous);

  void nextTick(() => {
    if (!scrollContainer.value) return;

    scrollContainer.value.scrollLeft = Math.max(0, fraction * chartWidth.value - centreOffset);
    onScroll();
  });
});

// A single rAF-coalesced render: several props change together on every data load,
// and re-entering d3's enter/update/exit per change is what makes charts stutter.
let frame: number | null = null;
function requestRender(): void {
  if (frame !== null) return;

  frame = requestAnimationFrame(() => {
    frame = null;
    render();
  });
}

watch(
  () => [
    props.layoutRows, props.cadences, props.highlightedCadenceIds, props.domain, props.monthWidthPx,
    props.selectedIds, props.staged, props.diagnosticSeverityByRow,
  ],
  requestRender,
  { deep: false },
);

let ghostLayer: d3.Selection<SVGGElement, unknown, null, undefined> | null = null;
let dragState: BarDragState | null = null;

const laneByRowId = computed(() => {
  const map = new Map<string, { top: number; height: number }>();

  for (const lane of props.layoutRows) {
    if (lane.row) map.set(lane.row.id, { top: lane.top, height: lane.height });
  }

  return map;
});

/**
 * Redrawn per pointermove, never through requestRender(): a full render rebuilds every
 * bar in the chart, which at Parlamentas scale is what would make a drag stutter.
 */
function drawGhost(state: BarDragState | null): void {
  if (!ghostLayer) return;

  renderDragGhost({
    layer: ghostLayer,
    x: scale.value,
    colors: colors.value,
    innerWidth: chartWidth.value,
    innerHeight: props.totalHeight,
    state,
    laneFor: rowId => laneByRowId.value.get(rowId),
  });
}

const rowsRef = computed(() => props.rows);
const cadencesRef = computed(() => props.cadences);
const stagedRef = computed(() => props.staged);
const selectedRef = computed(() => props.selectedIds);
const monthWidthRef = computed(() => props.monthWidthPx);

const drag = useBarDrag({
  svg: chartSvg,
  scale,
  monthWidthPx: monthWidthRef,
  rows: rowsRef,
  cadences: cadencesRef,
  staged: stagedRef,
  selectedIds: selectedRef,
  onUpdate: (state) => {
    dragState = state;
    drawGhost(state);
  },
  onCommit: (entries: DragEntry[]) => emit('stage', entries.map(entry => ({
    rowId: entry.rowId,
    dates: { start_date: toDateString(entry.start), end_date: entry.end ? toDateString(entry.end) : null },
  }))),
});

let themeObserver: MutationObserver | null = null;

onMounted(async () => {
  await nextTick();
  render();
  drag.attach();
  scrollToToday();

  // The palette is read once per render, so a theme flip needs an explicit nudge.
  themeObserver = new MutationObserver(() => {
    colors.value = getGanttColors(isDarkModeActive());
    timelineColors.value = getTimelineColors(isDarkModeActive());
    requestRender();
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class', 'data-theme'] });
});

onUnmounted(() => {
  if (frame !== null) cancelAnimationFrame(frame);
  themeObserver?.disconnect();
  drag.detach();
});

defineExpose({ requestRender });
</script>
