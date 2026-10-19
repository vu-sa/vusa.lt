<template>
  <div ref="wrap" class="relative isolate w-full max-w-full outline-none" tabindex="0"
    :class="{ 'h-full flex flex-col': props.height === '100%' }">
    <MeetingsGanttToolbar :show-legend :institution-count="institutions.length"
      :show-only-with-activity :show-only-with-public-meetings
      :day-width="dayWidthPx" :group-by="groupBy" :sort-by="sortBy" :fullscreen-active :meetings-loading
      @show-legend-modal="emit('show-legend-modal')" @update:day-width="onScaleChange"
      @update:group-by="groupBy = $event" @update:sort-by="sortBy = $event" @fullscreen="emit('fullscreen', true)" />

    <div ref="chartArea" class="relative isolate flex w-full min-w-0 max-w-full border border-border" data-slot="meetings-gantt"
      :style="containerHeight ? { height: containerHeight } : {}"
      :class="{ 'flex-1 min-h-0 h-full': props.height === '100%' }">
      <!-- Left: sticky labels -->
      <div ref="leftLabels" class="relative isolate z-[35] shrink-0 overflow-hidden border-r border-border bg-card"
        :style="{ width: `${labelWidthPx}px` }">
        <div class="grid" :style="{ gridTemplateRows: `${GANTT_AXIS_HEIGHT}px repeat(${layoutRows.length}, ${GANTT_ROW_HEIGHT}px)` }">
          <!-- header spacer (align with axis height) -->
          <div class="sticky top-0 z-20 border-b border-border bg-muted/40" />
          <template v-for="(row, idx) in layoutRows" :key="row.key">
            <div v-if="row.type === 'group'"
              class="sticky top-[22px] z-[30] truncate border-b border-border bg-muted px-3 py-1 text-xs font-semibold text-foreground">
              {{ row.label }}
            </div>
            <div v-else class="flex items-center gap-2 truncate border-b px-3 text-sm" :class="[
              row.isRelated
                ? 'border-dashed border-border text-muted-foreground'
                : 'border-border/60 text-foreground',
            ]" :title="labelFor(row.institutionId!)">
              <div class="flex min-w-0 flex-1 items-center justify-between gap-2">
                <div class="flex min-w-0 items-center gap-1.5">
                  <!-- A related institution is marked by what it is; one the rep may not open, by a lock. -->
                  <span v-if="row.isRelated" class="shrink-0" :title="getRelationshipTooltip(row)">
                    <Link2 v-if="row.authorized !== false" class="size-3.5" :aria-label="$t('Susijusi institucija')" />
                    <Lock v-else class="size-3.5" :aria-label="$t('relationships.not_authorized')" />
                  </span>
                  <button type="button" :data-tour="idx === 1 ? 'gantt-institution-row' : undefined"
                    class="cursor-pointer truncate text-left hover:underline focus-visible:underline focus-visible:outline-none"
                    :aria-label="$t('Atidaryti instituciją') + ': ' + labelFor(row.institutionId!)"
                    @click="visitInstitution(row.institutionId!, $event)"
                    @auxclick.middle.prevent="visitInstitution(row.institutionId!, $event)"
                    @keydown.enter.prevent="visitInstitution(row.institutionId!, $event)">
                    {{ labelFor(row.institutionId!) }}
                  </button>
                  <span v-if="requestLabels.get(String(row.institutionId))" class="shrink-0 text-status-info" data-slot="gantt-row-request"
                    :title="requestLabels.get(String(row.institutionId))">
                    <MailQuestion class="size-3.5" :aria-label="requestLabels.get(String(row.institutionId))" />
                  </span>
                  <span v-if="props.institutionHasPublicMeetings?.[row.institutionId!]" class="shrink-0 text-muted-foreground"
                    :title="$t('Vieši posėdžiai')">
                    <Globe class="size-3.5" :aria-label="$t('Vieši posėdžiai')" />
                  </span>
                </div>
                <span v-if="rowDates.get(String(row.institutionId))" data-slot="gantt-row-date"
                  class="shrink-0 text-xs tabular-nums text-muted-foreground"
                  :title="rowDateTitle(rowDates.get(String(row.institutionId))!)">{{
                    labelLast(rowDates.get(String(row.institutionId))!.date) }}</span>
              </div>
            </div>
          </template>
        </div>

        <!-- Drag handle for the label column width, as on the duty timeline. -->
        <div
          class="absolute right-0 top-0 z-[40] h-full w-1.5 cursor-col-resize touch-none hover:bg-border"
          :class="{ 'bg-ring/40': isResizing }" role="separator" :aria-label="$t('Keisti stulpelio plotį')"
          aria-orientation="vertical" @pointerdown="startLabelResize" />
      </div>

      <!-- Right: scrollable timeline with sticky header -->
      <div ref="rightScroll" class="h-full w-0 min-w-0 flex-1 overflow-auto bg-background">
        <div class="sticky top-0 isolate z-30 border-b border-border bg-background">
          <svg ref="axisEl" role="img" :aria-label="$t('Laiko ašis')" class="block" :style="{ height: `${GANTT_AXIS_HEIGHT}px` }" />
        </div>
        <svg ref="svgEl" role="img" :aria-label="$t('visak.overview.timeline.title')" class="block" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import * as d3 from 'd3';
import { Globe, Link2, Lock, MailQuestion } from '@lucide/vue';

import { getGanttColors, isDarkModeActive } from './ganttColors';
import MeetingsGanttToolbar from './MeetingsGanttToolbar.vue';
import {
  useGanttInteractions,
  useGanttViewport,
  useGanttLayout,
  useGanttFiltering,
  useGanttLabels,
  useColumnResize,
  useDragSelection,
  type ParsedDutyMember,
} from './composables';
import { GANTT_AXIS_HEIGHT, GANTT_ROW_HEIGHT, type GanttInstitutionMeta } from './composables/useGanttLayout';
import type { GanttRowDate } from './composables/useGanttLabels';
import {
  setupDefs,
  renderBackground,
  renderAxis,
  renderVacationBackgrounds,
  renderVacationOverlay,
  renderMeetings,
  renderActivityRequests,
  renderGaps,
  renderDutyMembers,
  renderInactivePeriods,
  renderTodayLine,
  renderHoverEffects,
  renderDragSelection,
  setupDragSelectionPattern,
  createGanttTooltip,
  createCenterLine,
  type CenterLineManager,
  type ParsedActivityRequest,
} from './renderers';

import { useVacationPeriods } from '@/Composables/useVacationPeriods';
import { useGanttSettings } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

/** Rows = institutions (grouped by padalinys or type), icons = meetings, striped bars = check-ins. */

export interface GanttActivityRequestInput {
  institution_id: string | number;
  campaign: 'activity_confirmation' | 'missing_meetings';
  period_start: string;
  period_end: string;
  sent_at: string | null;
  expires_at: string;
  recipients_count: number;
}

const props = withDefaults(defineProps<{
  meetings: Array<{ id: string | number; start_time: string | Date; institution_id: string | number; title?: string; institution?: string; has_calendar_event?: boolean; calendar_event_is_draft?: boolean; type_slug?: string; completion_status?: 'complete' | 'incomplete' | 'no_items'; authorized?: boolean }>;
  gaps: Array<{ institution_id: string | number; from: string | Date; until: string | Date; mode?: 'heads_up' | 'no_meetings'; note?: string }>;
  institutions?: Array<GanttInstitutionMeta & { name?: string; is_internal?: boolean }>;
  institutionNames?: Record<string | number, string>;
  tenantNames?: Record<string | number, string>;
  institutionHasPublicMeetings?: Record<string | number, boolean>;
  /** "Has any past/planned activity", independent of the loaded meeting windows */
  institutionHasActivity?: Record<string | number, boolean>;
  showLegend?: boolean;
  showTodayLine?: boolean;
  interactive?: boolean;
  showOnlyWithActivity?: boolean;
  showOnlyWithPublicMeetings?: boolean;
  /** Hide VU SA's own bodies. Off by default — everything is drawn unless asked otherwise. */
  hideInternalInstitutions?: boolean;
  /** '100%' in focus mode; otherwise the chart fits its rows up to `maxHeight` */
  height?: string;
  maxHeight?: number;
  dutyMembers?: Array<{ institution_id: string | number; user: { id: string; name: string; profile_photo_path?: string | null; activityCategory?: 'today' | 'week' | 'month' | 'stale' | 'never'; lastAction?: string | null }; start_date: string | Date; end_date?: string | Date | null }>;
  inactivePeriods?: Array<{ institution_id: string | number; from: string | Date; until: string | Date }>;
  showDutyMembers?: boolean;
  /** Activity rings on member avatars (padaliniai view only) */
  showActivityStatus?: boolean;
  /** Days between expected meetings, per institution */
  institutionPeriodicity?: Record<string | number, number>;
  /** Date range being fetched, drawn as a pulsing band */
  loadingRange?: { from: Date; until: Date } | null;
  meetingsLoading?: boolean;
  /** In focus mode the toolbar's full-screen button reads as "exit" */
  fullscreenActive?: boolean;
  activityRequests?: GanttActivityRequestInput[];
  showActivityRequests?: boolean;
}>(), {
  showLegend: true,
  showTodayLine: true,
  interactive: true,
  showOnlyWithActivity: false,
  showOnlyWithPublicMeetings: false,
  hideInternalInstitutions: false,
  height: undefined,
  maxHeight: 720,
  showDutyMembers: true,
  showActivityStatus: false,
  showActivityRequests: true,
});

const emit = defineEmits<{
  (e: 'create-meeting', payload: { institution_id: string | number; suggestedAt: Date; institutionName?: string }): void;
  (e: 'create-check-in', payload: { institution_id: string | number; startDate: Date; endDate: Date }): void;
  (e: 'fullscreen', payload: boolean): void;
  (e: 'show-legend-modal'): void;
  /** The dates on screen changed — consumers lazy-load meetings for them */
  (e: 'range-changed', min: Date, max: Date): void;
}>();

const DAYS_AROUND_TODAY = 60;
// Rendered beyond the viewport so ordinary scrolling rarely reveals a placeholder.
const HORIZONTAL_BUFFER_PX = 600;
const VERTICAL_BUFFER_PX = 900;
const margin = { top: 0, right: 8, bottom: 0, left: 8 };

const wrap = ref<HTMLElement | null>(null);
const chartArea = ref<HTMLElement | null>(null);
const rightScroll = ref<HTMLElement | null>(null);
const leftLabels = ref<HTMLElement | null>(null);
const svgEl = ref<SVGSVGElement | null>(null);
const axisEl = ref<SVGSVGElement | null>(null);
const curXRef = ref<d3.ScaleTime<number, number> | null>(null);
let centerLineManager: CenterLineManager | null = null;

const page = usePage();
const locale = () => (page.props.app as { locale?: string } | undefined)?.locale ?? 'lt';

const ganttSettings = useGanttSettings();
const { dayWidthPx, groupBy, sortBy, centerDateTimestamp, setCenterDate, verticalScrollPosition, setVerticalScrollPosition } = ganttSettings;
const labelWidthPx = ganttSettings.labelWidth;

const { isResizing, startResize: startLabelResize } = useColumnResize(ganttSettings.setLabelWidth, () => labelWidthPx.value);

const visitInstitution = (id: string | number, event?: MouseEvent | KeyboardEvent) => {
  const url = route('institutions.show', id);
  if (event && (event.ctrlKey || event.metaKey || (event instanceof MouseEvent && event.button === 1))) {
    window.open(url, '_blank');
  }
  else {
    router.visit(url);
  }
};

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

const { institutions, filteredMeetings, filteredGaps, filteredDutyMembers, filteredInactivePeriods } = useGanttFiltering(
  {
    showOnlyWithActivity: () => props.showOnlyWithActivity,
    showOnlyWithPublicMeetings: () => props.showOnlyWithPublicMeetings,
    hideInternalInstitutions: () => props.hideInternalInstitutions,
    institutionHasPublicMeetings: () => props.institutionHasPublicMeetings,
    institutionHasActivity: () => props.institutionHasActivity,
    showDutyMembers: () => props.showDutyMembers,
    sortBy: () => sortBy.value,
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

const { mergedTenantNames, labelFor, rowDateFor, fmtDate, fmtDateWithYear, labelLast, getRelationshipTooltip } = useGanttLabels(
  { institutionNames: () => props.institutionNames, tenantNames: () => props.tenantNames, sortBy: () => sortBy.value },
  { institutions: () => props.institutions, filteredMeetings, filteredGaps },
);

const rowDates = computed(() => new Map(institutions.value.map(id => [String(id), rowDateFor(id)])));
const rowDateTitle = ({ date, kind }: GanttRowDate) => `${$t(`visak.gantt.row_date.${kind}`)}: ${fmtDateWithYear.format(date)}`;

const { layoutRows, rowTop, rowHeightFor, rowCenter, contentHeight, containerHeight } = useGanttLayout(
  { groupBy, height: () => props.height, maxHeight: props.maxHeight },
  { institutions, institutionsMeta: () => props.institutions, tenantNames: mergedTenantNames },
);

const rowIndexByKey = computed(() => new Map(layoutRows.value.map((row, index) => [row.key, index])));

// --- Render scheduling -------------------------------------------------------------------------
// Every trigger (data, scroll past a threshold, zoom, resize, theme) asks for a render; one runs per frame.

let renderFrame: number | null = null;
const afterRenderQueue: Array<() => void> = [];

function scheduleRender(): void {
  if (renderFrame !== null) return;
  renderFrame = requestAnimationFrame(() => {
    renderFrame = null;
    render();
  });
}

function afterRender(callback: () => void): void {
  afterRenderQueue.push(callback);
  scheduleRender();
}

const {
  extraBefore,
  extraAfter,
  minTime,
  maxTime,
  applyInitialExtension,
  applyInitialScrollPosition,
  onScaleChange,
  navigateToToday,
  setupVerticalScrollSync,
  attachScrollHandler,
  attachKeyboardHandler,
} = useGanttInteractions(
  {
    daysBefore: DAYS_AROUND_TODAY,
    daysAfter: DAYS_AROUND_TODAY,
    infiniteScroll: true,
    extendThresholdPx: 200,
    extendStepDays: 30,
    centerDateTimestamp,
  },
  { rightScroll, leftLabels, curX: curXRef, dayWidthPx },
  { afterRender },
);

// The backend's vacation calendar; winter vacation starts in December, so the year before the range counts too.
const { periods: vacationPeriods, ensureYears: ensureVacationYears } = useVacationPeriods();
watch([minTime, maxTime], ([min, max]) => ensureVacationYears(min.getFullYear() - 1, max.getFullYear()), { immediate: true });

// Vertical thresholds are coarser than horizontal ones: each change re-renders the SVG, which competes
// with the labels column's scroll sync and made it stutter at a finer step.
const viewport = useGanttViewport(rightScroll, curXRef, {
  bufferPx: HORIZONTAL_BUFFER_PX,
  verticalBufferPx: VERTICAL_BUFFER_PX,
  verticalScrollThreshold: 250,
  onViewportChange: scheduleRender,
});

// Rows marked `authorized: false` are read-only: someone else's padalinys or an unauthorized relation.
const readOnlyInstitutionIds = computed(() => new Set(
  (props.institutions ?? []).filter(institution => institution.authorized === false).map(institution => String(institution.id)),
));

function canCreateOn(institutionId: string | number): boolean {
  return !readOnlyInstitutionIds.value.has(String(institutionId));
}

function createMeetingOn(payload: { institution_id: string | number; suggestedAt: Date }): void {
  if (canCreateOn(payload.institution_id)) {
    emit('create-meeting', { ...payload, institutionName: labelFor(payload.institution_id) });
  }
}

const dragSelection = useDragSelection(rightScroll, svgEl, curXRef, layoutRows, {
  onDragComplete: (payload) => {
    if (canCreateOn(payload.institution_id)) {
      emit('create-check-in', payload);
    }
  },
});

// --- Activity requests ------------------------------------------------------------------------

const openRequests = computed(() => (props.showActivityRequests ? props.activityRequests ?? [] : []).map(request => ({
  ...request,
  fromDate: d3.timeDay.floor(new Date(request.period_start)),
  untilDate: d3.timeDay.offset(d3.timeDay.floor(new Date(request.period_end)), 1),
})));

/** Which open requests an institution has, named for its label's mark. */
const requestLabels = computed(() => {
  const labels = new Map<string, string>();
  for (const request of openRequests.value) {
    const id = String(request.institution_id);
    const label = $t(`activity_requests.campaigns.${request.campaign}`);
    labels.set(id, labels.has(id) ? `${labels.get(id)} · ${label}` : label);
  }
  return labels;
});

/** Meetings whose records an open "Papildyk posėdžių įrašus" request asks for. */
const requestedMeetingIds = computed(() => {
  const ids = new Set<string>();
  const periods = openRequests.value.filter(request => request.campaign === 'missing_meetings');
  if (periods.length === 0) return ids;

  for (const meeting of parsedMeetings.value) {
    if (meeting.completion_status === 'complete' || meeting.authorized === false) continue;
    const asked = periods.some(period => String(period.institution_id) === String(meeting.institution_id)
      && meeting.date >= period.fromDate && meeting.date < period.untilDate);
    if (asked) ids.add(String(meeting.id));
  }
  return ids;
});

// --- Render ------------------------------------------------------------------------------------

function render(): void {
  const container = wrap.value;
  const el = rightScroll.value;
  const svg = d3.select(svgEl.value);
  const axisSvg = d3.select(axisEl.value);
  if (!container || !el || svg.empty()) return;

  const isDark = isDarkModeActive();
  const colors = getGanttColors(isDark);

  const totalDays = Math.max(1, d3.timeDay.count(minTime.value, maxTime.value));
  // Slightly wider than the viewport, so there is always a horizontal scrollbar to extend with.
  const innerW = Math.max(totalDays * dayWidthPx.value, el.clientWidth + 50);
  const height = Math.max(50, contentHeight.value);
  const innerWidth = innerW - margin.left - margin.right;

  svg.attr('width', innerW).attr('height', height);
  svg.selectAll('*').remove();
  axisSvg.attr('width', innerW).attr('height', GANTT_AXIS_HEIGHT);
  axisSvg.selectAll('*').remove();

  const x = d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth]);
  curXRef.value = x;
  // Before measuring what is on screen: the first render decides where the view starts.
  applyInitialScrollPosition(x, margin.left);

  const visibleLeft = el.scrollLeft - margin.left;
  const visibleRight = visibleLeft + el.clientWidth;
  const drawMin = x.invert(Math.max(0, visibleLeft - HORIZONTAL_BUFFER_PX));
  const drawMax = x.invert(Math.min(innerWidth, visibleRight + HORIZONTAL_BUFFER_PX));
  const top = el.scrollTop - VERTICAL_BUFFER_PX;
  const bottom = el.scrollTop + el.clientHeight + VERTICAL_BUFFER_PX;

  const visibleRows = layoutRows.value.filter(row => row.top + row.height >= top && row.top <= bottom);
  const visibleInstitutionRows = visibleRows
    .filter(row => row.type === 'institution')
    .map(row => ({ key: row.key, institutionId: row.institutionId! }));
  const visibleKeys = new Set(visibleRows.map(row => row.key));
  const rowKeysByInstitution = new Map<string, string[]>();
  for (const row of visibleInstitutionRows) {
    const keys = rowKeysByInstitution.get(String(row.institutionId)) ?? [];
    keys.push(row.key);
    rowKeysByInstitution.set(String(row.institutionId), keys);
  }

  /** Each item once per visible row of its institution, inside the drawn date range. */
  const place = <T extends { institution_id: string | number }>(items: T[], overlaps: (item: T) => boolean): Array<T & { rowKey: string }> =>
    items.flatMap(item => (overlaps(item) ? (rowKeysByInstitution.get(String(item.institution_id)) ?? []).map(rowKey => ({ ...item, rowKey })) : []));

  const meetings = place(filteredMeetings.value, m => m.date >= drawMin && m.date <= drawMax);
  const gaps = place(filteredGaps.value, gap => gap.untilDate >= drawMin && gap.fromDate <= drawMax);
  const members = place(filteredDutyMembers.value, m => (m.endDate ?? drawMax) >= drawMin && m.startDate <= drawMax);
  const requests: ParsedActivityRequest[] = place(
    openRequests.value.filter(request => request.campaign === 'activity_confirmation'),
    request => request.untilDate >= drawMin && request.fromDate <= drawMax,
  ).map(request => ({
    institution_id: request.institution_id,
    rowKey: request.rowKey,
    fromDate: request.fromDate,
    untilDate: request.untilDate,
    sentAt: request.sent_at ? new Date(request.sent_at) : null,
    expiresAt: new Date(request.expires_at),
    recipients_count: request.recipients_count,
  }));

  const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`);
  const defs = svg.append('defs');
  setupDefs({ defs, colors, isDarkMode: isDark });
  setupDragSelectionPattern(defs, isDark);

  const tooltipManager = createGanttTooltip(container, colors);

  centerLineManager?.destroy();
  if (chartArea.value) {
    centerLineManager = createCenterLine({
      container: chartArea.value,
      rightScroll: el,
      x,
      colors,
      marginLeft: margin.left,
      axisHeight: GANTT_AXIS_HEIGHT,
      locale: locale(),
      isDarkMode: isDark,
      onNavigateToToday: () => {
        setCenterDate(null);
        navigateToToday();
      },
    });
  }

  const rowGeometry = { rowTop, rowHeightFor };

  renderBackground({
    g,
    x,
    layoutRows: visibleRows,
    innerWidth,
    innerHeight: height,
    colors,
    minTime: drawMin,
    maxTime: drawMax,
    ...rowGeometry,
    dayWidthPx: dayWidthPx.value,
    rowIndex: key => rowIndexByKey.value.get(String(key)) ?? 0,
  });

  // Rows outside the rendered band: a placeholder, so a fast scroll reveals a skeleton, not blank space.
  g.append('g')
    .attr('class', 'gantt-row-skeletons')
    .selectAll('rect')
    .data(layoutRows.value.filter(row => !visibleKeys.has(row.key)))
    .enter()
    .append('rect')
    .attr('class', 'gantt-row-skeleton')
    .attr('x', 0)
    .attr('y', d => d.top + 4)
    .attr('width', innerWidth)
    .attr('height', d => d.height - 8);

  // Opaque layer first so it covers the grid; the tint goes on top of everything (renderVacationOverlay).
  const vacationContext = {
    g,
    x,
    layoutRows: visibleRows,
    innerWidth,
    minTime: drawMin,
    maxTime: drawMax,
    vacationPeriods: vacationPeriods.value,
    colors,
    ...rowGeometry,
  };
  renderVacationBackgrounds(vacationContext);

  if (props.loadingRange) {
    const x0 = Math.max(0, x(props.loadingRange.from), visibleLeft);
    const x1 = Math.min(innerWidth, x(props.loadingRange.until), visibleRight);
    if (x1 > x0) {
      g.append('g')
        .attr('class', 'gantt-loading-rows')
        .selectAll('rect')
        .data(visibleInstitutionRows)
        .enter()
        .append('rect')
        .attr('class', 'gantt-loading-range')
        .attr('x', x0)
        .attr('y', d => rowTop(d.key) + 5)
        .attr('width', x1 - x0)
        .attr('height', GANTT_ROW_HEIGHT - 10);
    }
  }

  if (props.showDutyMembers) {
    renderInactivePeriods({
      g,
      x,
      innerWidth,
      inactivePeriods: filteredInactivePeriods.value,
      dutyMembers: filteredDutyMembers.value.filter(m => rowKeysByInstitution.has(String(m.institution_id))),
      minTime: drawMin,
      maxTime: drawMax,
      ...rowGeometry,
      rows: visibleInstitutionRows,
    });
  }

  renderAxis({
    axisSvg,
    x,
    marginLeft: margin.left,
    axisHeight: GANTT_AXIS_HEIGHT,
    dayWidthPx: dayWidthPx.value,
    minTime: drawMin,
    maxTime: drawMax,
    colors,
    locale: locale(),
  });

  renderActivityRequests({ g, container, x, requests, colors, ...rowGeometry, visibleLeft, visibleRight, tooltipManager });

  renderGaps({ g, x, gaps, colors, rowCenter, ...rowGeometry, onCreateMeeting: createMeetingOn });

  renderMeetings({
    g,
    container,
    x,
    meetings,
    colors,
    rowCenter,
    ...rowGeometry,
    labelFor,
    interactive: true,
    tooltipManager,
    institutionPeriodicity: props.institutionPeriodicity,
    requestedMeetingIds: requestedMeetingIds.value,
  });

  if (props.showDutyMembers) {
    const groups = new Map<string, { rowKey: string; startDate: Date; members: ParsedDutyMember[] }>();
    for (const member of members) {
      const key = `${member.rowKey}|${member.startDate.toDateString()}`;
      const group = groups.get(key) ?? { rowKey: member.rowKey, startDate: member.startDate, members: [] };
      group.members.push(member);
      groups.set(key, group);
    }

    renderDutyMembers({
      g,
      defs,
      container,
      x,
      groupedDutyMembers: Array.from(groups.values()),
      innerWidth,
      colors,
      ...rowGeometry,
      tooltipManager,
      showActivityStatus: props.showActivityStatus,
    });
  }

  renderVacationOverlay(vacationContext);

  renderTodayLine({ g, x, innerHeight: height, minTime: minTime.value, maxTime: maxTime.value, colors, showTodayLine: props.showTodayLine });

  renderHoverEffects({
    g,
    container,
    x,
    innerHeight: height,
    layoutRows: visibleRows,
    meetings,
    gaps,
    colors,
    ...rowGeometry,
    rowCenter,
    labelFor,
    fmtDateWithYear,
    fmtDate,
    interactive: props.interactive,
    canCreateOn,
    tooltipManager,
    onCreateMeeting: createMeetingOn,
  });

  emit('range-changed', x.invert(Math.max(0, visibleLeft)), x.invert(visibleRight));

  afterRenderQueue.splice(0).forEach(callback => callback());
}

watch(
  [
    parsedMeetings, parsedGaps, parsedDutyMembers, parsedInactivePeriods, institutions, layoutRows, vacationPeriods,
    openRequests, extraBefore, extraAfter, dayWidthPx,
    () => props.showDutyMembers, () => props.showActivityStatus, () => props.loadingRange, () => props.institutionPeriodicity,
  ],
  scheduleRender,
);

watch(() => dragSelection.state.value, (state) => {
  const g = d3.select(svgEl.value).select<SVGGElement>('g');
  if (g.empty() || !curXRef.value) return;

  renderDragSelection({ g, x: curXRef.value, dragState: state, colors: getGanttColors(isDarkModeActive()), isDarkMode: isDarkModeActive() });
}, { deep: true });

onMounted(() => {
  applyInitialExtension();
  scheduleRender();

  const resizeObserver = new ResizeObserver(() => viewport.forceUpdate());
  if (wrap.value) resizeObserver.observe(wrap.value);

  const themeObserver = new MutationObserver(scheduleRender);
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

  const cleanups = [
    setupVerticalScrollSync(props.height === '100%'),
    attachScrollHandler(),
    viewport.attachViewportTracking(),
    attachKeyboardHandler(wrap.value),
    props.interactive ? dragSelection.attachDragHandler() : undefined,
  ];

  // The view's position survives a reload: center date and vertical scroll, saved while scrolling.
  const savePosition = () => {
    const el = rightScroll.value;
    if (!el || !curXRef.value) return;
    setCenterDate(curXRef.value.invert(el.scrollLeft + el.clientWidth / 2 - margin.left));
    setVerticalScrollPosition(el.scrollTop);
  };
  let saveTimeout: ReturnType<typeof setTimeout> | null = null;
  const onScroll = () => {
    centerLineManager?.update();
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(savePosition, 200);
  };
  rightScroll.value?.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('beforeunload', savePosition);

  if (verticalScrollPosition.value != null) {
    nextTick(() => {
      if (rightScroll.value) {
        rightScroll.value.scrollTop = verticalScrollPosition.value ?? 0;
      }
    });
  }

  onUnmounted(() => {
    if (renderFrame !== null) cancelAnimationFrame(renderFrame);
    if (saveTimeout) clearTimeout(saveTimeout);
    resizeObserver.disconnect();
    themeObserver.disconnect();
    cleanups.forEach(cleanup => cleanup?.());
    centerLineManager?.destroy();
    rightScroll.value?.removeEventListener('scroll', onScroll);
    window.removeEventListener('beforeunload', savePosition);
    savePosition();
  });
});
</script>

<style scoped>
svg :global(text) {
  font-size: 11px;
  fill: var(--muted-foreground);
}

/* Pulsing skeleton pill per row whose meetings are being fetched */
svg :global(.gantt-loading-range) {
  fill: var(--muted-foreground);
  animation: gantt-loading-pulse 1.6s ease-in-out infinite;
  pointer-events: none;
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

/* Rows outside the rendered band: subtler and slower than the loading pulse — "not drawn yet", not "fetching". */
svg :global(.gantt-row-skeleton) {
  fill: var(--muted-foreground);
  animation: gantt-row-skeleton-pulse 2s ease-in-out infinite;
  pointer-events: none;
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
