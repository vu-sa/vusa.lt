<template>
  <div ref="wrap" class="relative w-full max-w-full outline-none" tabindex="0"
    :class="{ 'h-full flex flex-col': props.height === '100%' }">
    <!-- Header: Legend + Controls (VDOM child — keeps reka-ui Slider/Tooltip out of the vapor tree) -->
    <MeetingsGanttToolbar :show-legend :institution-count="layoutRows.filter(r => r.type === 'institution').length"
      :tenant-filter :tenant-names="mergedTenantNames" :show-only-with-activity :show-only-with-public-meetings
      :details-expanded :day-width="dayWidthPx || dayWidth" :hide-fullscreen-button :meetings-loading
      @show-legend-modal="emit('show-legend-modal')" @scroll-to-tenant="scrollToTenant"
      @update:details-expanded="emit('update:detailsExpanded', $event)" @update:day-width="onScaleChange([$event])"
      @fullscreen="emit('fullscreen', true)" />

    <div class="flex w-full max-w-full border border-zinc-200 dark:border-zinc-700 rounded-md"
      :style="containerHeight ? { height: containerHeight } : {}"
      :class="{ 'flex-1 min-h-0 h-full': props.height === '100%' }" style="min-width: 0;">
      <!-- Left: sticky labels -->
      <div ref="leftLabels" class="shrink-0 bg-white dark:bg-zinc-900 z-[35] overflow-hidden"
        :style="{ width: `${labelWidthPx}px` }" style="isolation: isolate;">
        <div class="grid" :style="{ gridTemplateRows: `22px ${layoutRows.map(r => r.height + 'px').join(' ')}` }">
          <!-- header spacer (align with axis height) -->
          <div class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 sticky top-0 z-20" />
          <template v-for="(row, idx) in layoutRows" :key="`label-${row.key}`">
            <div v-if="row.type === 'tenant'"
              class="px-3 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 sticky top-[22px] z-[30]">
              {{ mergedTenantNames[row.tenantId!] ?? row.tenantId }}
            </div>
            <div v-else class="px-3 py-1 text-sm border-b flex items-start gap-2 truncate" :class="[
              idx % 2 === 0 ? 'bg-zinc-50/40 dark:bg-zinc-800/30' : '',
              row.isRelated && row.authorized !== false
                ? 'text-zinc-500 dark:text-zinc-400 border-zinc-100 dark:border-zinc-800 border-dashed bg-blue-50/30 dark:bg-blue-900/10'
                : row.isRelated && row.authorized === false
                  ? 'text-zinc-400 dark:text-zinc-500 border-zinc-100 dark:border-zinc-800 border-dashed bg-amber-50/30 dark:bg-amber-900/10'
                  : 'text-zinc-700 dark:text-zinc-300 border-zinc-100 dark:border-zinc-800'
            ]" :title="labelFor(row.institutionId!)">
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <!-- Related institution indicator - authorized (blue) or unauthorized (amber) -->
                    <div v-if="row.isRelated" class="relative group shrink-0" :title="getRelationshipTooltip(row)">
                      <svg
                        :class="['h-3 w-3', row.authorized !== false ? 'text-blue-500 dark:text-blue-400' : 'text-amber-500 dark:text-amber-400']"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round"
                        :aria-label="row.authorized !== false ? $t('Susijusi institucija') : $t('relationships.not_authorized')">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                      </svg>
                    </div>
                    <button type="button" :data-tour="idx === 1 ? 'gantt-institution-row' : undefined"
                      class="truncate text-left hover:underline cursor-pointer focus:underline focus:outline-none"
                      :class="[row.isRelated ? 'opacity-80' : '']"
                      :aria-label="$t('Atidaryti instituciją') + ': ' + (labelFor(row.institutionId!) || row.institutionId)"
                      @click="visitInstitution(row.institutionId!, $event)"
                      @auxclick.middle.prevent="visitInstitution(row.institutionId!, $event)"
                      @keydown.enter.prevent="visitInstitution(row.institutionId!, $event)">
                      {{ labelFor(row.institutionId!) }}
                    </button>
                    <!-- Public meetings indicator -->
                    <svg v-if="props.institutionHasPublicMeetings?.[row.institutionId!]"
                      class="h-3 w-3 text-green-600 dark:text-green-500/70 shrink-0" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      :aria-label="$t('Vieši posėdžiai')">
                      <circle cx="12" cy="12" r="10" />
                      <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20" />
                      <path d="M2 12h20" />
                    </svg>
                  </div>
                  <span v-if="lastMeetingByInstitution.get(row.institutionId!)"
                    class="text-[11px] text-zinc-500 dark:text-zinc-500 shrink-0">{{
                      labelLast(lastMeetingByInstitution.get(row.institutionId!)!) }}</span>
                </div>
                <div v-if="detailsExpanded" class="mt-1 text-[11px] text-zinc-600 dark:text-zinc-500 leading-snug">
                  <div class="truncate">
                    <span class="opacity-70">{{ $t('Susitikimų') }}:</span>
                    <span class="ml-1">{{meetings.filter(m => m.institution_id === row.institutionId).length}}</span>
                  </div>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- Resize handle for label column -->
      <div
        class="w-1 shrink-0 cursor-col-resize bg-transparent hover:bg-blue-500/30 active:bg-blue-500/50 transition-colors z-[40]"
        :class="{ 'bg-blue-500/50': isResizing }" role="separator" :aria-label="$t('Keisti stulpelio plotį')"
        aria-orientation="vertical" @mousedown.prevent="startLabelResize" />

      <!-- Right: scrollable timeline with sticky header -->
      <div ref="rightScroll" class="flex-1 overflow-auto min-w-0 h-full bg-white dark:bg-zinc-900"
        style="width: 0; min-width: 0;">
        <!-- Sticky x-axis header - uses isolate to create new stacking context -->
        <div ref="axisScroll"
          class="sticky top-0 z-30 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700"
          style="isolation: isolate;">
          <svg ref="axisEl" role="img" aria-label="Timeline axis" class="block" style="height: 22px;" />
        </div>
        <!-- Chart content -->
        <svg ref="svgEl" role="img" aria-label="Meetings timeline" class="block" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import * as d3 from 'd3';

import { getGanttColors, isDarkModeActive, type GanttColors } from './ganttColors';
import MeetingsGanttToolbar from './MeetingsGanttToolbar.vue';
import {
  useGanttInteractions,
  useGanttViewport,
  useGanttLayout,
  useGanttFiltering,
  useGanttLabels,
  useColumnResize,
  useDragSelection,
  type LayoutRow,
  type ParsedDutyMember,
} from './composables';
import {
  setupDefs,
  renderBackground,
  renderAxis,
  renderVacationBackgrounds,
  renderVacationOverlay,
  renderMeetings,
  renderGaps,
  renderDutyMembers,
  renderInactivePeriods,
  renderTodayLine,
  renderHoverEffects,
  renderDragSelection,
  setupDragSelectionPattern,
  createGanttTooltip,
  createCenterLine,
  type GanttTooltipManager,
  type CenterLineManager,
} from './renderers';

import { useVacationPeriods } from '@/Composables/useVacationPeriods';
import { useGanttSettings } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

/**
 * MeetingsGantt (d3)
 * - Rows = institutions; circles = meetings; bars = gaps.
 */

const props = withDefaults(defineProps<{
  meetings: Array<{ id: string | number; start_time: string | Date; institution_id: string | number; title?: string; institution?: string; type_slug?: string }>;
  gaps: Array<{ institution_id: string | number; from: string | Date; until: string | Date; mode?: 'heads_up' | 'no_meetings'; note?: string }>;
  institutions?: Array<{ id: string | number; name?: string; tenant_id?: string | number; is_related?: boolean; relationship_direction?: 'outgoing' | 'incoming' | 'sibling'; relationship_type?: 'direct' | 'type-based' | 'within-type'; source_institution_id?: string; authorized?: boolean }>;
  daysBefore?: number;
  daysAfter?: number;
  dayWidth?: number;
  startDate?: string | Date;
  institutionsOrder?: Array<string | number>;
  rowHeight?: number;
  institutionNames?: Record<string | number, string>;
  labelWidth?: number;
  // Optional tenant categorization and filtering
  tenantFilter?: Array<string | number>;
  institutionTenant?: Record<string | number, string | number>;
  tenantNames?: Record<string | number, string>;
  // Public meetings indicator lookup
  institutionHasPublicMeetings?: Record<string | number, boolean>;
  // "Has any past/planned activity" lookup for the activity filter (independent of loaded windows)
  institutionHasActivity?: Record<string | number, boolean>;
  // UI toggles
  showLegend?: boolean;
  showTodayLine?: boolean;
  interactive?: boolean;
  showOnlyWithActivity?: boolean;
  showOnlyWithPublicMeetings?: boolean;
  // Row details/expansion (global multi-expand)
  detailsExpanded?: boolean;
  expandedRowHeight?: number;
  // Infinite scroll controls
  infiniteScroll?: boolean;
  extendStepDays?: number;
  extendThresholdPx?: number;
  // Container height
  height?: string;
  // Duty members display
  dutyMembers?: Array<{ institution_id: string | number; user: { id: string; name: string; profile_photo_path?: string | null; activityCategory?: 'today' | 'week' | 'month' | 'stale' | 'never'; lastAction?: string | null }; start_date: string | Date; end_date?: string | Date | null }>;
  inactivePeriods?: Array<{ institution_id: string | number; from: string | Date; until: string | Date }>;
  showDutyMembers?: boolean;
  // Activity status rings for duty member avatars (tenant view only)
  showActivityStatus?: boolean;
  // Meeting periodicity per institution (days between expected meetings)
  institutionPeriodicity?: Record<string | number, number>;
  // Date range currently being loaded (rendered as a shimmer band)
  loadingRange?: { from: Date; until: Date } | null;
  // Whether meetings are currently being fetched (shown as a toolbar indicator)
  meetingsLoading?: boolean;
  // Hide fullscreen button (when already in fullscreen modal)
  hideFullscreenButton?: boolean;
}>(), {
  daysBefore: 60,
  daysAfter: 60,
  dayWidth: 24,
  rowHeight: 28,
  labelWidth: 220,
  showLegend: true,
  showTodayLine: true,
  interactive: true,
  showOnlyWithActivity: false,
  showOnlyWithPublicMeetings: false,
  detailsExpanded: false,
  expandedRowHeight: 56,
  infiniteScroll: true,
  extendStepDays: 30,
  extendThresholdPx: 200,
  height: '400px',
  showDutyMembers: true,
  showActivityStatus: false,
});

const wrap = ref<HTMLElement | null>(null);
const rightScroll = ref<HTMLElement | null>(null);
const axisScroll = ref<HTMLElement | null>(null);
const leftLabels = ref<HTMLElement | null>(null);
const svgEl = ref<SVGSVGElement | null>(null);
const axisEl = ref<SVGSVGElement | null>(null);
let ro: ResizeObserver | null = null;
// curX as ref so it can be passed to composables
const curXRef = ref<d3.ScaleTime<number, number> | null>(null);
// Center line manager for scroll updates
let centerLineManager: CenterLineManager | null = null;

// Use injected Gantt settings (eliminates prop drilling for dayWidth, etc.)
// Falls back to local settings if no provider is found (standalone usage)
const ganttSettings = useGanttSettings();
const { dayWidthPx } = ganttSettings;
const labelWidthPx = ganttSettings.labelWidth;
const { setLabelWidth } = ganttSettings;
const { showTenantHeaders } = ganttSettings;
const { centerDateTimestamp } = ganttSettings;
const { setCenterDate } = ganttSettings;
const { verticalScrollPosition } = ganttSettings;
const { setVerticalScrollPosition } = ganttSettings;

// Column resize composable for label column
const { isResizing, startResize: startLabelResize } = useColumnResize(
  setLabelWidth,
  () => labelWidthPx.value,
);

const emit = defineEmits<{
  (e: 'create-meeting', payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }): void;
  (e: 'create-check-in', payload: { institution_id: string | number; startDate: Date; endDate: Date }): void;
  (e: 'fullscreen', payload: boolean): void;
  (e: 'update:detailsExpanded', payload: boolean): void;
  (e: 'show-legend-modal'): void;
  /** Timeline range changed (scroll extension, year navigation) — consumers may lazy-load data for it */
  (e: 'range-changed', min: Date, max: Date): void;
}>();

// Navigate to institution details (admin route helper if available)
const visitInstitution = (id: string | number, event?: MouseEvent | KeyboardEvent) => {
  // @ts-ignore route helper might be globally available (ziggy)
  const routeFn = (window as any)?.route;
  const url = routeFn ? routeFn('institutions.show', id) : `/admin/institutions/${id}`;
  // Support Ctrl/Cmd+click to open in new tab
  if (event && (event.ctrlKey || event.metaKey || (event instanceof MouseEvent && event.button === 1))) {
    window.open(url, '_blank');
  }
  else {
    router.visit(url);
  }
};

// Parse data props into Date objects
const parsedMeetings = computed(() => props.meetings.map(m => ({ ...m, date: new Date(m.start_time) })));
const parsedGaps = computed(() => props.gaps.map(g => ({ ...g, fromDate: new Date(g.from), untilDate: new Date(g.until) })));
const parsedDutyMembers = computed(() => (props.dutyMembers ?? []).map(m => ({
  ...m,
  startDate: new Date(m.start_date),
  endDate: m.end_date ? new Date(m.end_date) : null,
})));
const parsedInactivePeriods = computed(() => (props.inactivePeriods ?? []).map(p => ({
  ...p,
  fromDate: new Date(p.from),
  untilDate: new Date(p.until),
})));

// Filtering composable: institutions, filtered collections, grouping
const filtering = useGanttFiltering(
  {
    tenantFilter: () => props.tenantFilter,
    institutionTenant: () => props.institutionTenant,
    showOnlyWithActivity: () => props.showOnlyWithActivity,
    showOnlyWithPublicMeetings: () => props.showOnlyWithPublicMeetings,
    institutionHasPublicMeetings: () => props.institutionHasPublicMeetings,
    institutionHasActivity: () => props.institutionHasActivity,
    institutionsOrder: () => props.institutionsOrder,
    showDutyMembers: () => props.showDutyMembers,
  },
  {
    parsedMeetings,
    parsedGaps,
    parsedDutyMembers,
    parsedInactivePeriods,
    institutions: () => props.institutions,
    institutionNames: () => props.institutionNames,
  },
);
const { institutions, filteredMeetings, filteredGaps, filteredDutyMembers, filteredInactivePeriods } = filtering;

// Labels composable: name lookups, formatting, tooltips
const labels = useGanttLabels(
  {
    institutionNames: () => props.institutionNames,
    tenantNames: () => props.tenantNames,
    institutionTenant: () => props.institutionTenant,
  },
  {
    institutions: () => props.institutions,
    meetings: () => props.meetings,
    filteredMeetings,
  },
);
const { mergedTenantNames, labelFor, tenantFor, lastMeetingByInstitution, fmtDate, fmtDateWithYear, labelLast, getRelationshipTooltip } = labels;

// Layout composable: rows, positions, heights
const layout = useGanttLayout(
  {
    rowHeight: () => props.rowHeight,
    expandedRowHeight: () => props.expandedRowHeight,
    detailsExpanded: () => props.detailsExpanded,
    height: () => props.height,
    showTenantHeaders,
  },
  {
    institutions,
    institutionsMeta: () => props.institutions,
    institutionTenant: () => props.institutionTenant,
    mergedTenantNames,
  },
);
const { layoutRows, rowTop, rowHeightFor, rowCenter, containerHeight } = layout;

// Page props for locale access
const page = usePage();

// Initialize interaction composable for scroll, zoom, and navigation
const interactions = useGanttInteractions(
  {
    daysBefore: props.daysBefore,
    daysAfter: props.daysAfter,
    infiniteScroll: props.infiniteScroll,
    extendThresholdPx: props.extendThresholdPx ?? 200,
    extendStepDays: props.extendStepDays ?? 30,
    startDate: props.startDate ? new Date(props.startDate) : null,
    centerDateTimestamp, // Pass ref, not value
  },
  {
    rightScroll,
    leftLabels,
    curX: curXRef,
    layoutRows: computed(() => layoutRows.value.map(r => ({ key: r.key, top: r.top }))),
    dayWidthPx,
  },
  {
    onDayWidthChange: (width: number) => ganttSettings.setDayWidth(width),
  },
);

// Destructure commonly used values from interactions composable
const {
  extraBefore,
  extraAfter,
  extending,
  currentYear,
  didInitialAutoScroll,
  minTime,
  maxTime,
  applyInitialExtension,
  applyInitialScrollPosition,
  onScroll,
  onScaleChange,
  navigateYears,
  navigateToToday,
  scrollToTenant,
  setupVerticalScrollSync,
  attachScrollHandler,
  attachKeyboardHandler,
  updateCurrentYear,
} = interactions;

// Vacation periods come from the backend, which uses the same calendar when
// deciding whether an institution has gone too long without a meeting.
// Winter vacation starts in December and ends on January 1st, so the year before
// the visible range can still contribute a period.
const { periods: vacationPeriods, ensureYears: ensureVacationYears } = useVacationPeriods();

watch([minTime, maxTime], ([min, max]) => {
  ensureVacationYears(min.getFullYear() - 1, max.getFullYear());
}, { immediate: true });

// Initialize viewport composable for horizontal + vertical culling (performance optimization)
// Viewport changes re-render through onViewportChange — the culled computeds must not
// be watched directly, because render() reassigns curXRef and would loop.
// Buffers are generous on both axes so normal scrolling stays within the
// pre-rendered/pre-fetched zone and rarely reveals a skeleton.
// verticalScrollThreshold is much coarser than the horizontal one: each
// viewport change re-renders the whole SVG, which competes for main-thread
// time with the labels column's scroll sync (see useGanttInteractions'
// setupVerticalScrollSync) — triggering that on every ~50px of vertical
// scroll made the labels visibly stutter/catch up during fast scrolling.
// The large verticalBufferPx means content stays pre-rendered well past
// this coarser trigger point regardless.
const viewport = useGanttViewport(rightScroll, curXRef, {
  bufferPx: 600,
  verticalBufferPx: 900,
  verticalScrollThreshold: 250,
  onViewportChange: () => {
    emitVisibleRange();
    render();
  },
});

// Create viewport-culled data for rendering (horizontal / date-based)
const visibleMeetings = viewport.createVisibleMeetings(filteredMeetings);
const visibleGaps = viewport.createVisibleGaps(filteredGaps);
const visibleDutyMembers = viewport.createVisibleDutyMembers(filteredDutyMembers);

// Vertically-culled rows: only these get drawn. Row count scales with the
// number of institutions across all selected tenants, so this is what keeps
// an all-units view cheap to render.
const visibleLayoutRows = viewport.createVisibleRows(layoutRows);

// Absolute index of each row in the *full* (uncalled) row list, so zebra
// striping stays anchored to each row's true position instead of resetting
// from the first row that happens to be in view.
const rowIndexByKey = computed(() => {
  const m = new Map<string | number, number>();
  layoutRows.value.forEach((r, i) => m.set(r.key, i));
  return m;
});
const rowIndex = (key: string | number) => rowIndexByKey.value.get(key) ?? 0;

const visibleInstitutionIds = computed(() => {
  const ids = new Set<string | number>();
  for (const row of visibleLayoutRows.value) {
    if (row.type === 'institution' && row.institutionId !== undefined) {
      ids.add(String(row.institutionId));
    }
  }
  return ids;
});

// Rows that exist in the layout but are currently culled (outside the
// rendered viewport+buffer) — drawn as a plain pulsing placeholder so a fast
// scroll or a jump-to-tenant reveals a skeleton instead of blank space before
// the real content renders on the next viewport update.
const culledLayoutRows = computed(() => {
  const visibleKeys = new Set(visibleLayoutRows.value.map(r => r.key));
  return layoutRows.value.filter(r => !visibleKeys.has(r.key));
});

// Meetings/gaps/duty members culled by BOTH date (viewport) and vertical
// position (visibleLayoutRows) — an institution scrolled out of view has no
// need for its meetings/avatars to be computed either.
const rowCulledMeetings = computed(() =>
  visibleMeetings.value.filter(m => visibleInstitutionIds.value.has(String(m.institution_id))));
const rowCulledGaps = computed(() =>
  visibleGaps.value.filter(g => visibleInstitutionIds.value.has(String(g.institution_id))));
const rowCulledDutyMembers = computed(() =>
  visibleDutyMembers.value.filter(m => visibleInstitutionIds.value.has(String(m.institution_id))));

/**
 * Emit the currently visible date range so consumers can lazy-load data for it.
 * The visible range (not the whole timeline span) is what needs meetings —
 * the timeline itself can extend years beyond the viewport via infinite scroll.
 */
function emitVisibleRange() {
  const bounds = viewport.viewportBounds.value;
  if (bounds) {
    emit('range-changed', bounds.minDate, bounds.maxDate);
  }
}

// Group viewport-culled duty members by institution + day for avatar stacking
const groupedVisibleDutyMembers = computed<Map<string, ParsedDutyMember[]>>(() => {
  const groups = new Map<string, ParsedDutyMember[]>();
  for (const member of rowCulledDutyMembers.value) {
    const dayKey = `${member.institution_id}:${member.startDate.toDateString()}`;
    const arr = groups.get(dayKey) ?? [];
    arr.push(member as ParsedDutyMember);
    groups.set(dayKey, arr);
  }
  return groups;
});

// Initialize drag selection composable for Shift+drag check-in creation
const dragSelection = useDragSelection(
  rightScroll,
  svgEl,
  curXRef,
  layoutRows,
  {
    onDragComplete: (payload) => {
      emit('create-check-in', payload);
    },
  },
);

// Margins: top is 0 since x-axis is now in a separate sticky SVG.
// Bottom set to 0 so SVG height matches the left grid height exactly.
const margin = { top: 0, right: 8, bottom: 0, left: 8 };
const axisHeight = 22; // Height of the sticky x-axis header

/**
 * Main render function for the gantt chart
 *
 * Renders the complete gantt chart using D3.js including:
 * - Time axis (top) with weekly ticks and year markers
 * - Institution rows with zebra striping and tenant grouping
 * - Meeting dots (circles) that are clickable
 * - Check-in bars (lines) with visual distinction for expired ones
 * - Green safety bands around meetings (±14 days)
 * - Vacation period overlays (summer, winter, easter)
 * - Interactive hover effects and tooltips
 * - Today line indicator
 *
 * The function is re-run whenever reactive dependencies change (meetings,
 * gaps, date range, filters, etc.)
 */
const render = () => {
  const container = wrap.value;
  const svg = d3.select(svgEl.value);
  const axisSvg = axisEl.value ? d3.select(axisEl.value) : null;
  if (!container || svg.empty()) return;

  // Get color palette based on current theme
  const colors = getGanttColors(isDarkModeActive());

  // derive width from current date span
  const totalDays = Math.max(1, d3.timeDay.count(minTime.value, maxTime.value));
  const viewportW = (rightScroll.value?.clientWidth ?? container.clientWidth) || 800;
  const calculatedW = totalDays * (dayWidthPx.value || props.dayWidth);
  // Ensure minimum width slightly larger than viewport to guarantee horizontal scrollbar
  const innerW = Math.max(calculatedW, viewportW + 50);
  const rowsH = layoutRows.value.reduce((acc, r) => acc + r.height, 0);

  // Calculate the ideal content height (rows only, axis is separate)
  const idealHeight = rowsH;

  // Use the ideal height directly - the container will handle overflow
  const height = Math.max(50, idealHeight); // Ensure minimum height

  svg.attr('width', innerW).attr('height', height);
  svg.selectAll('*').remove();

  // Also set axis SVG width to match
  if (axisSvg && !axisSvg.empty()) {
    axisSvg.attr('width', innerW).attr('height', axisHeight);
    axisSvg.selectAll('*').remove();
  }

  const innerWidth = innerW - margin.left - margin.right;
  const innerH = height - margin.top - margin.bottom;

  const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);

  // gradients and patterns
  const defs = svg.append('defs');
  setupDefs({
    defs,
    colors,
    isDarkMode: isDarkModeActive(),
  });
  // Add drag selection pattern for Shift+drag check-in creation
  setupDragSelectionPattern(defs, isDarkModeActive());

  // Create unified tooltip manager for all renderers
  // Remove old tooltip elements first to prevent duplicates
  d3.select(container).selectAll('.gantt-tooltip, .gantt-tooltip-create, .gantt-tooltip-member, .gantt-unified-tooltip').remove();
  const tooltipManager = createGanttTooltip(container, colors);

  // Create or update center line indicator
  if (centerLineManager) {
    centerLineManager.destroy();
  }
  if (rightScroll.value) {
    const currentLocale = (page.props.app as any)?.locale ?? 'lt';
    centerLineManager = createCenterLine({
      container: container as HTMLElement,
      rightScroll: rightScroll.value,
      x: d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth]),
      colors,
      marginLeft: margin.left,
      axisHeight,
      locale: currentLocale,
      isDarkMode: isDarkModeActive(),
      onNavigateToToday: () => {
        // Clear stored center date and navigate to today
        setCenterDate(null);
        navigateToToday();
      },
    });
  }

  const x = d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth]);
  curXRef.value = x;
  // Variable-row layout handled manually via rowTop/rowHeightFor — no band scale

  // Render background (zebra rows, Monday grid, year markers, row separators)
  // — only for vertically-visible rows; rowIndex keeps zebra parity anchored
  // to each row's true position rather than the culled subset's local index.
  renderBackground({
    g,
    x,
    layoutRows: visibleLayoutRows.value,
    innerWidth,
    innerHeight: innerH,
    colors,
    minTime: minTime.value,
    maxTime: maxTime.value,
    rowTop,
    rowHeightFor,
    dayWidthPx: dayWidthPx.value,
    rowIndex,
  });

  // Placeholder for rows outside the rendered viewport+buffer — a plain
  // pulsing bar instead of nothing, so a fast scroll or jump-to-tenant reveals
  // a skeleton rather than blank space until the next viewport update fills it in.
  if (culledLayoutRows.value.length > 0) {
    g.append('g')
      .attr('class', 'gantt-row-skeletons')
      .selectAll('rect')
      .data(culledLayoutRows.value)
      .enter()
      .append('rect')
      .attr('class', 'gantt-row-skeleton')
      .attr('x', 0)
      .attr('y', d => rowTop(d.key) + 4)
      .attr('width', innerWidth)
      .attr('height', d => rowHeightFor(d.key) - 8)
      .attr('rx', 3)
      .attr('ry', 3);
  }

  // Vacation period bands (Layer 1 only: opaque solid background covering the
  // Sunday/zebra grid). Must render early so meetings/gaps/avatars, drawn
  // later, still show through. The colored overlay (Layer 2-3) renders LAST
  // — see renderVacationOverlay() below — so its tint reads consistently
  // instead of blending with e.g. a meeting's green safety band.
  renderVacationBackgrounds({
    g,
    x,
    layoutRows: visibleLayoutRows.value,
    innerWidth,
    minTime: minTime.value,
    maxTime: maxTime.value,
    vacationPeriods: vacationPeriods.value,
    colors,
    rowTop,
    rowHeightFor,
  });

  // Loading skeleton: a pulsing pill per visible institution row, clipped to
  // the pending fetch range intersected with the horizontal viewport — a
  // full-height wash was too faint to register as "loading" at all.
  if (props.loadingRange) {
    const bounds = viewport.viewportBounds.value;
    const rangeX0 = Math.max(0, x(props.loadingRange.from));
    const rangeX1 = Math.min(innerWidth, x(props.loadingRange.until));
    const clipX0 = bounds ? Math.max(rangeX0, bounds.left) : rangeX0;
    const clipX1 = bounds ? Math.min(rangeX1, bounds.right) : rangeX1;

    if (clipX1 > clipX0) {
      const skeletonRows = visibleLayoutRows.value.filter(r => r.type === 'institution');

      g.append('g')
        .attr('class', 'gantt-loading-rows')
        .selectAll('rect')
        .data(skeletonRows)
        .enter()
        .append('rect')
        .attr('class', 'gantt-loading-range')
        .attr('x', clipX0)
        .attr('y', d => rowTop(d.key) + 5)
        .attr('width', clipX1 - clipX0)
        .attr('height', d => rowHeightFor(d.key) - 10)
        .attr('rx', 4)
        .attr('ry', 4);
    }
  }

  // Inactive periods (no active duty members) - render as diagonal striped rectangles
  if (props.showDutyMembers) {
    renderInactivePeriods({
      g,
      x,
      innerWidth,
      inactivePeriods: filteredInactivePeriods.value,
      dutyMembers: rowCulledDutyMembers.value,
      minTime: minTime.value,
      maxTime: maxTime.value,
      rowTop,
      rowHeightFor,
      allInstitutionIds: Array.from(visibleInstitutionIds.value),
    });
  }

  // Render sticky x-axis in separate SVG using extracted renderer
  const currentLocale = (page.props.app as any)?.locale ?? 'lt';
  if (axisSvg && !axisSvg.empty()) {
    renderAxis({
      axisSvg,
      x,
      marginLeft: margin.left,
      axisHeight,
      dayWidthPx: dayWidthPx.value || props.dayWidth,
      minTime: minTime.value,
      maxTime: maxTime.value,
      colors,
      locale: currentLocale,
    });
  }

  // gaps (check-ins) as striped rectangles with CalendarOff icons - using extracted renderer
  renderGaps({
    g,
    x,
    gaps: rowCulledGaps.value,
    colors,
    rowCenter,
    rowTop,
    rowHeightFor,
    onCreateMeeting: (payload: { institution_id: string | number; suggestedAt: Date }) => {
      // Include institution name in the payload for external institutions
      const name = labelFor(payload.institution_id);
      emit('create-meeting', { ...payload, institutionName: name });
    },
  });

  // Render meeting icons with safety bands and tooltips using extracted renderer
  renderMeetings({
    g,
    container: container as HTMLElement,
    x,
    meetings: rowCulledMeetings.value,
    colors,
    rowCenter,
    rowTop,
    rowHeightFor,
    labelFor,
    interactive: true,
    tooltipManager,
    institutionPeriodicity: props.institutionPeriodicity,
  });

  // Duty member avatar markers using extracted renderer
  if (props.showDutyMembers) {
    renderDutyMembers({
      g,
      defs,
      container: container as HTMLElement,
      x,
      groupedDutyMembers: groupedVisibleDutyMembers.value,
      innerWidth,
      detailsExpanded: props.detailsExpanded,
      colors,
      rowTop,
      rowHeightFor,
      tooltipManager,
      showActivityStatus: props.showActivityStatus,
    });
  }

  // Vacation overlay (Layers 2-3: colored tint + borders) — drawn LAST among
  // content layers so the vacation hue reads consistently on top of meeting
  // safety bands, gaps, and avatars rather than blending with them (`pointer-
  // events: none`, so clicks still reach the elements underneath).
  renderVacationOverlay({
    g,
    x,
    layoutRows: visibleLayoutRows.value,
    innerWidth,
    minTime: minTime.value,
    maxTime: maxTime.value,
    vacationPeriods: vacationPeriods.value,
    colors,
    rowTop,
    rowHeightFor,
  });

  // Today line using extracted renderer
  renderTodayLine({
    g,
    x,
    innerHeight: innerH,
    minTime: minTime.value,
    maxTime: maxTime.value,
    colors,
    showTodayLine: props.showTodayLine,
  });

  // Hover effects and click-to-create using extracted renderer
  renderHoverEffects({
    g,
    container: container as HTMLElement,
    x,
    innerHeight: innerH,
    layoutRows: visibleLayoutRows.value,
    meetings: rowCulledMeetings.value,
    gaps: rowCulledGaps.value,
    colors,
    rowTop,
    rowHeightFor,
    rowCenter,
    labelFor,
    fmtDateWithYear,
    fmtDate,
    interactive: props.interactive,
    tooltipManager,
    onCreateMeeting: (payload) => {
      // Include institution name in the payload for external institutions
      const name = labelFor(payload.institution_id);
      emit('create-meeting', { ...payload, institutionName: name });
    },
  });

  // Apply initial scroll position using the composable's function
  // This centers on the stored center date (from localStorage) or today
  applyInitialScrollPosition(x, margin.left);

  // Update current year badge from center of viewport
  updateCurrentYear();
}; // end render

onMounted(() => {
  // Apply initial extension for low zoom levels BEFORE first render
  // This ensures the timeline has the correct range when we calculate initial scroll position
  applyInitialExtension();

  // Now render with correct extensions already applied
  render();
  ro = new ResizeObserver(() => viewport.forceUpdate());
  if (wrap.value) ro.observe(wrap.value);

  // Watch for dark mode changes via MutationObserver on document.documentElement
  const themeObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.attributeName === 'class') {
        // Re-render when theme class changes
        render();
        break;
      }
    }
  });
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  // Setup vertical scroll synchronization using composable
  const cleanupVerticalSync = setupVerticalScrollSync(props.height === '100%');

  // Attach infinite scroll handler using composable
  const cleanupScrollHandler = attachScrollHandler();

  // Attach viewport tracking for horizontal culling
  const cleanupViewport = viewport.attachViewportTracking();

  // Attach keyboard navigation handler
  const cleanupKeyboard = attachKeyboardHandler(wrap.value);

  // Attach Shift+drag handler for check-in creation (interactive only)
  const cleanupDragSelection = props.interactive ? dragSelection.attachDragHandler() : () => { };

  // Setup center line scroll handler with debounced center date saving
  let saveCenterDateTimeout: ReturnType<typeof setTimeout> | null = null;
  let saveVerticalScrollTimeout: ReturnType<typeof setTimeout> | null = null;

  // Helper function to save current center date immediately
  const saveCurrentCenterDate = () => {
    if (rightScroll.value && curXRef.value) {
      const { scrollLeft } = rightScroll.value;
      const viewportWidth = rightScroll.value.clientWidth;
      const xScalePosition = scrollLeft + viewportWidth / 2 - margin.left;
      const centerDate = curXRef.value.invert(xScalePosition);
      setCenterDate(centerDate);
    }
  };

  // Helper function to save current vertical scroll position immediately
  const saveCurrentVerticalScroll = () => {
    if (rightScroll.value) {
      setVerticalScrollPosition(rightScroll.value.scrollTop);
    }
  };

  const handleCenterLineScroll = () => {
    centerLineManager?.update();

    // Debounced save of center date to localStorage (200ms delay for faster persistence)
    if (saveCenterDateTimeout) clearTimeout(saveCenterDateTimeout);
    saveCenterDateTimeout = setTimeout(saveCurrentCenterDate, 200);

    // Debounced save of vertical scroll position to localStorage
    if (saveVerticalScrollTimeout) clearTimeout(saveVerticalScrollTimeout);
    saveVerticalScrollTimeout = setTimeout(saveCurrentVerticalScroll, 200);
  };
  rightScroll.value?.addEventListener('scroll', handleCenterLineScroll, { passive: true });

  // Also save on beforeunload to catch any pending scroll position
  const handleBeforeUnload = () => {
    if (saveCenterDateTimeout) {
      clearTimeout(saveCenterDateTimeout);
    }
    if (saveVerticalScrollTimeout) {
      clearTimeout(saveVerticalScrollTimeout);
    }
    saveCurrentCenterDate();
    saveCurrentVerticalScroll();
  };
  window.addEventListener('beforeunload', handleBeforeUnload);

  // Restore vertical scroll position after initial render
  if (verticalScrollPosition.value != null && rightScroll.value) {
    nextTick(() => {
      if (rightScroll.value) {
        rightScroll.value.scrollTop = verticalScrollPosition.value ?? 0;
      }
    });
  }

  // Store cleanup functions for onUnmounted
  onUnmounted(() => {
    ro?.disconnect();
    themeObserver.disconnect();
    cleanupVerticalSync?.();
    cleanupScrollHandler?.();
    cleanupViewport?.();
    cleanupKeyboard?.();
    cleanupDragSelection?.();
    centerLineManager?.destroy();
    rightScroll.value?.removeEventListener('scroll', handleCenterLineScroll);
    window.removeEventListener('beforeunload', handleBeforeUnload);
    if (saveCenterDateTimeout) clearTimeout(saveCenterDateTimeout);
    if (saveVerticalScrollTimeout) clearTimeout(saveVerticalScrollTimeout);
    // Save final position on unmount
    saveCurrentCenterDate();
    saveCurrentVerticalScroll();
  });
});

// Watch drag selection state to render selection rectangle in real-time
watch(() => dragSelection.state.value, (state) => {
  const svg = d3.select(svgEl.value);
  if (svg.empty() || !curXRef.value) return;

  const g = svg.select<SVGGElement>('g');
  if (g.empty()) return;

  const colors = getGanttColors(isDarkModeActive());

  renderDragSelection({
    g,
    x: curXRef.value,
    dragState: state,
    colors,
    isDarkMode: isDarkModeActive(),
  });
}, { deep: true });

// NOTE: the viewport-culled computeds (visibleMeetings etc.) are intentionally NOT
// watched here — they depend on curXRef, which render() itself reassigns, so watching
// them causes recursive updates. Viewport changes re-render via onViewportChange.
watch([parsedMeetings, parsedGaps, parsedDutyMembers, parsedInactivePeriods, institutions, layoutRows, vacationPeriods, () => props.daysBefore, () => props.daysAfter, () => props.startDate, () => props.tenantFilter, () => props.showOnlyWithActivity, () => props.showOnlyWithPublicMeetings, () => props.showDutyMembers, () => props.showActivityStatus, () => props.detailsExpanded, () => props.loadingRange, extraBefore, extraAfter, dayWidthPx], () => render());
</script>

<style scoped>
svg :global(text) {
  font-size: 10px;
  fill: rgb(113, 113, 122);
}

:global(.dark) svg :global(text) {
  fill: rgb(161, 161, 170);
}

.row-hover:hover {
  background: rgba(0, 0, 0, 0.03);
}

:global(.dark) .row-hover:hover {
  background: rgba(255, 255, 255, 0.05);
}

/* Pulsing skeleton pill per row whose meetings are being fetched */
svg :global(.gantt-loading-range) {
  fill: rgb(113, 113, 122);
  animation: gantt-loading-pulse 1.6s ease-in-out infinite;
  pointer-events: none;
}

:global(.dark) svg :global(.gantt-loading-range) {
  fill: rgb(161, 161, 170);
}

@keyframes gantt-loading-pulse {

  0%,
  100% {
    opacity: 0.1;
  }

  50% {
    opacity: 0.24;
  }
}

/* Placeholder bar for rows outside the rendered viewport+buffer — subtler and
   slower than the meetings-loading pulse, since it means "not rendered yet",
   not "actively fetching". */
svg :global(.gantt-row-skeleton) {
  fill: rgb(113, 113, 122);
  animation: gantt-row-skeleton-pulse 2s ease-in-out infinite;
  pointer-events: none;
}

:global(.dark) svg :global(.gantt-row-skeleton) {
  fill: rgb(161, 161, 170);
}

@keyframes gantt-row-skeleton-pulse {

  0%,
  100% {
    opacity: 0.06;
  }

  50% {
    opacity: 0.13;
  }
}
</style>
