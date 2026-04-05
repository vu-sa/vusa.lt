<template>
  <div ref="wrap" class="relative w-full max-w-full outline-none" tabindex="0" :class="{ 'h-full flex flex-col': props.height === '100%' }">
    <!-- Header: Legend + Controls -->
    <div class="flex items-center justify-between gap-3 mb-2">
      <div class="flex items-center gap-4 min-w-0">
        <!-- Legend toggle button -->
        <button
          v-if="showLegend"
          type="button"
          data-tour="gantt-legend"
          class="flex items-center gap-1.5 text-[11px] text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors"
          @click="$emit('show-legend-modal')"
        >
          <span class="inline-block w-2 h-2 rounded-full bg-foreground dark:bg-white" />
          <span>{{ $t('Legenda') }}</span>
          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-60" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
          </svg>
        </button>
        <!-- Institution count and active filters -->
        <div class="flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400 truncate">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded border border-zinc-200 dark:border-zinc-600 bg-white/70 dark:bg-zinc-800/70">{{
            $t('Institucijų') }}: {{ layoutRows.filter(r => r.type === 'institution').length }}</span>
          <template v-if="tenantFilter?.length">
            <span class="opacity-70">•</span>
            <div class="flex items-center gap-1 truncate">
              <span class="opacity-70 shrink-0">{{ $t('Filtras') }}:</span>
              <div class="flex items-center gap-1 overflow-hidden">
                <button v-for="tid in tenantFilter" :key="String(tid)"
                  type="button"
                  class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600 truncate hover:bg-zinc-200 dark:hover:bg-zinc-600 cursor-pointer transition-colors"
                  :title="$t('Slinkti į') + ' ' + (mergedTenantNames[tid] ?? tid)"
                  @click="scrollToTenant(tid)">
                  {{ mergedTenantNames[tid] ?? tid }}
                </button>
              </div>
            </div>
          </template>
          <template v-if="showOnlyWithActivity">
            <span class="opacity-70">•</span>
            <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600">{{ $t('Tik su veikla') }}</span>
          </template>
          <template v-if="showOnlyWithPublicMeetings">
            <span class="opacity-70">•</span>
            <span class="px-1.5 py-0.5 rounded bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-600">{{ $t('Viešos institucijos') }}</span>
          </template>
        </div>
      </div>
      <div class="flex items-center gap-2 ml-auto">
        <!-- Details toggle - icon button with tooltip -->
        <Tooltip>
          <TooltipTrigger as-child>
            <button type="button"
              class="p-1.5 rounded border text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors"
              :class="detailsExpanded ? 'bg-zinc-100 dark:bg-zinc-700 border-zinc-300 dark:border-zinc-500' : 'border-zinc-200 dark:border-zinc-600'"
              @click="emit('update:detailsExpanded', !detailsExpanded)">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
              </svg>
            </button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Išsamios eilutės') }}</TooltipContent>
        </Tooltip>
        <!-- Scale slider -->
        <div data-tour="gantt-scale" class="w-40 flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400">
          <span class="shrink-0">{{ $t('Mastelis') }}</span>
          <Slider :min="3" :max="36" :step="1" :model-value="[dayWidthPx || dayWidth]"
            @update:model-value="onScaleChange" />
        </div>
        <!-- Fullscreen button - icon with tooltip -->
        <Tooltip v-if="!hideFullscreenButton">
          <TooltipTrigger as-child>
            <button type="button" data-tour="gantt-fullscreen"
              class="p-1.5 rounded border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-600 dark:text-zinc-400 transition-colors"
              @click="$emit('fullscreen', true)">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 11-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd" />
              </svg>
            </button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('Visas ekranas') }}</TooltipContent>
        </Tooltip>
      </div>
    </div>

    <div class="flex w-full max-w-full border border-zinc-200 dark:border-zinc-700 rounded-md"
      :style="containerHeight ? { height: containerHeight } : {}" :class="{ 'flex-1 min-h-0 h-full': props.height === '100%' }"
      style="min-width: 0;">
      <!-- Left: sticky labels -->
      <div ref="leftLabels" class="shrink-0 bg-white dark:bg-zinc-900 z-[35] overflow-hidden"
        :style="{ width: `${labelWidthPx}px` }"
        style="isolation: isolate;">
        <div class="grid" :style="{ gridTemplateRows: `22px ${layoutRows.map(r => r.height + 'px').join(' ')}` }">
          <!-- header spacer (align with axis height) -->
          <div class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 sticky top-0 z-20" />
          <template v-for="(row, idx) in layoutRows" :key="`label-${row.key}`">
            <div v-if="row.type === 'tenant'"
              class="px-3 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 sticky top-[22px] z-[30]">
              {{ mergedTenantNames[row.tenantId!] ?? row.tenantId }}
            </div>
            <div v-else
              class="px-3 py-1 text-sm border-b flex items-start gap-2 truncate"
              :class="[
                idx % 2 === 0 ? 'bg-zinc-50/40 dark:bg-zinc-800/30' : '',
                row.isRelated && row.authorized !== false
                  ? 'text-zinc-500 dark:text-zinc-400 border-zinc-100 dark:border-zinc-800 border-dashed bg-blue-50/30 dark:bg-blue-900/10'
                  : row.isRelated && row.authorized === false
                    ? 'text-zinc-400 dark:text-zinc-500 border-zinc-100 dark:border-zinc-800 border-dashed bg-amber-50/30 dark:bg-amber-900/10'
                    : 'text-zinc-700 dark:text-zinc-300 border-zinc-100 dark:border-zinc-800'
              ]"
              :title="labelFor(row.institutionId!)">
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <!-- Related institution indicator - authorized (blue) or unauthorized (amber) -->
                    <div v-if="row.isRelated"
                      class="relative group shrink-0"
                      :title="getRelationshipTooltip(row)">
                      <svg
                        :class="['h-3 w-3', row.authorized !== false ? 'text-blue-500 dark:text-blue-400' : 'text-amber-500 dark:text-amber-400']"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        :aria-label="row.authorized !== false ? $t('Susijusi institucija') : $t('relationships.not_authorized')">
                        <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" />
                        <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                      </svg>
                    </div>
                    <button type="button"
                      :data-tour="idx === 1 ? 'gantt-institution-row' : undefined"
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
                      class="h-3 w-3 text-green-600 dark:text-green-500/70 shrink-0"
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
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
                    <span class="ml-1">{{ meetings.filter(m => m.institution_id === row.institutionId).length }}</span>
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
        :class="{ 'bg-blue-500/50': isResizing }"
        role="separator"
        :aria-label="$t('Keisti stulpelio plotį')"
        aria-orientation="vertical"
        @mousedown.prevent="startLabelResize"
      />

      <!-- Right: scrollable timeline with sticky header -->
      <div ref="rightScroll" class="flex-1 overflow-auto min-w-0 h-full bg-white dark:bg-zinc-900" style="width: 0; min-width: 0;">
        <!-- Sticky x-axis header - uses isolate to create new stacking context -->
        <div ref="axisScroll" class="sticky top-0 z-30 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700" style="isolation: isolate;">
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
import {
  useGanttInteractions,
  useGanttViewport,
  useGanttLayout,
  useGanttFiltering,
  useGanttLabels,
  useColumnResize,
  useDragSelection,
  type LayoutRow,
} from './composables';
import {
  setupDefs,
  renderBackground,
  renderAxis,
  renderVacations,
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

import { Slider } from '@/Components/ui/slider';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/Components/ui/tooltip';
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
const { institutions, filteredMeetings, filteredGaps, filteredDutyMembers, filteredInactivePeriods, groupedDutyMembers } = filtering;

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

// Import vacation configuration
import { getVacationPeriods } from '@/Pages/Admin/Dashboard/Components/vacationConfig';

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

// Initialize viewport composable for horizontal culling (performance optimization)
const viewport = useGanttViewport(rightScroll, curXRef, { bufferPx: 300 });

// Create viewport-culled data for rendering
const visibleMeetings = viewport.createVisibleMeetings(filteredMeetings);
const visibleGaps = viewport.createVisibleGaps(filteredGaps);
const visibleDutyMembers = viewport.createVisibleDutyMembers(filteredDutyMembers);

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
  renderBackground({
    g,
    x,
    layoutRows: layoutRows.value,
    innerWidth,
    innerHeight: innerH,
    colors,
    minTime: minTime.value,
    maxTime: maxTime.value,
    rowTop,
    rowHeightFor,
    dayWidthPx: dayWidthPx.value,
  });

  // Render vacation period bands using extracted renderer
  renderVacations({
    g,
    x,
    layoutRows: layoutRows.value,
    innerWidth,
    minTime: minTime.value,
    maxTime: maxTime.value,
    colors,
    rowTop,
    rowHeightFor,
  });

  // Inactive periods (no active duty members) - render as diagonal striped rectangles
  if (props.showDutyMembers) {
    renderInactivePeriods({
      g,
      x,
      innerWidth,
      inactivePeriods: filteredInactivePeriods.value,
      dutyMembers: filteredDutyMembers.value,
      minTime: minTime.value,
      maxTime: maxTime.value,
      rowTop,
      rowHeightFor,
      allInstitutionIds: institutions.value,
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
    gaps: filteredGaps.value,
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
    meetings: filteredMeetings.value,
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
      groupedDutyMembers: groupedDutyMembers.value,
      innerWidth,
      detailsExpanded: props.detailsExpanded,
      colors,
      rowTop,
      rowHeightFor,
      tooltipManager,
      showActivityStatus: props.showActivityStatus,
    });
  }

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
    layoutRows: layoutRows.value,
    meetings: filteredMeetings.value,
    gaps: filteredGaps.value,
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
  ro = new ResizeObserver(() => render());
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
  const cleanupDragSelection = props.interactive ? dragSelection.attachDragHandler() : () => {};

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

watch([parsedMeetings, parsedGaps, parsedDutyMembers, parsedInactivePeriods, institutions, layoutRows, () => props.daysBefore, () => props.daysAfter, () => props.startDate, () => props.tenantFilter, () => props.showOnlyWithActivity, () => props.showOnlyWithPublicMeetings, () => props.showDutyMembers, () => props.showActivityStatus, () => props.detailsExpanded, extraBefore, extraAfter, dayWidthPx], () => render());
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
</style>
