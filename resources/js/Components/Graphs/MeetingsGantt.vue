<template>
  <div ref="wrap" class="relative w-full max-w-full" :class="{ 'h-full flex flex-col': props.height === '100%' }">
    <!-- Header: Legend + Controls -->
    <div class="flex items-center justify-between gap-3 mb-2">
      <div class="flex items-center gap-4 min-w-0">
        <!-- Legend toggle button -->
        <button 
          v-if="showLegend" 
          type="button"
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
            $t('Institucijų') }}: {{layoutRows.filter(r => r.type === 'institution').length}}</span>
          <template v-if="tenantFilter?.length">
            <span class="opacity-70">•</span>
            <div class="flex items-center gap-1 truncate">
              <span class="opacity-70 shrink-0">{{ $t('Filtras') }}:</span>
              <div class="flex items-center gap-1 overflow-hidden">
                <span v-for="tid in tenantFilter" :key="String(tid)"
                  class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600 truncate">
                  {{ mergedTenantNames[tid] ?? tid }}
                </span>
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
      <div class="flex items-center gap-3 ml-auto">
        <!-- Details toggle -->
        <label class="flex items-center gap-1 text-[11px] text-zinc-600 dark:text-zinc-400 select-none cursor-pointer">
          <input type="checkbox" :checked="!!detailsExpanded"
            @change="emit('update:detailsExpanded', !detailsExpanded)">
          <span>{{ $t('Išsamios eilutės') }}</span>
        </label>
        <!-- Scale slider -->
        <div class="w-40 flex items-center gap-2 text-[11px] text-zinc-600 dark:text-zinc-400">
          <span class="shrink-0">{{ $t('Mastelis') }}</span>
          <Slider :min="6" :max="72" :step="2" :model-value="[dayWidthPx || dayWidth]"
            @update:model-value="onScaleChange" />
        </div>
        <!-- Sticky current year badge -->
        <div v-if="currentYear"
          class="px-2 py-0.5 rounded border text-xs text-zinc-700 dark:text-zinc-300 bg-white/70 dark:bg-zinc-800/70 backdrop-blur border-zinc-200 dark:border-zinc-600">
          {{ currentYear }}
        </div>
        <button type="button" class="px-2 py-1 text-xs border border-zinc-200 dark:border-zinc-600 rounded hover:bg-zinc-50 dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-300"
          @click="$emit('fullscreen', true)">
          {{ $t('Visas ekranas') }}
        </button>
      </div>
    </div>

    <div class="flex w-full max-w-full border border-zinc-200 dark:border-zinc-700 rounded-md"
      :style="containerHeight ? { height: containerHeight } : {}" :class="{ 'flex-1 min-h-0 h-full': props.height === '100%' }"
      style="min-width: 0;">
      <!-- Left: sticky labels -->
      <div ref="leftLabels" class="shrink-0 bg-white dark:bg-zinc-900 z-[1]"
        :class="props.height === '100%' ? 'overflow-y-auto overflow-x-hidden' : 'overflow-hidden'"
        :style="{ width: `${labelWidth}px` }">
        <div class="grid" :style="{ gridTemplateRows: `22px ${layoutRows.map(r => r.height + 'px').join(' ')}` }">
          <!-- header spacer (align with axis height) -->
          <div class="border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 sticky top-0 z-20" />
          <template v-for="(row, idx) in layoutRows" :key="`label-${row.key}`">
            <div v-if="row.type === 'tenant'"
              class="px-3 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50/70 dark:bg-zinc-800/70 sticky top-[22px] z-[2]">
              {{ mergedTenantNames[row.tenantId!] ?? row.tenantId }}
            </div>
            <div v-else
              class="px-3 py-1 text-sm text-zinc-700 dark:text-zinc-300 border-b border-zinc-100 dark:border-zinc-800 flex items-start gap-2 truncate"
              :class="[idx % 2 === 0 ? 'bg-zinc-50/40 dark:bg-zinc-800/30' : '']" :title="labelFor(row.institutionId!)">
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <button type="button"
                      class="truncate text-left hover:underline cursor-pointer focus:underline focus:outline-none"
                      :aria-label="$t('Atidaryti instituciją') + ': ' + (labelFor(row.institutionId!) || row.institutionId)"
                      @click="visitInstitution(row.institutionId!)"
                      @keydown.enter.prevent="visitInstitution(row.institutionId!)">
                      {{ labelFor(row.institutionId!) }}
                    </button>
                    <!-- Public meetings indicator -->
                    <svg v-if="props.institutionHasPublicMeetings?.[row.institutionId!]" 
                      class="h-3 w-3 text-green-600 dark:text-green-400 shrink-0" 
                      viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                      :aria-label="$t('Vieši posėdžiai')">
                      <circle cx="12" cy="12" r="10"/>
                      <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                      <path d="M2 12h20"/>
                    </svg>
                  </div>
                  <span v-if="lastMeetingByInstitution.get(row.institutionId!)"
                    class="text-[11px] text-zinc-500 dark:text-zinc-500 shrink-0">{{
                      labelLast(lastMeetingByInstitution.get(row.institutionId!)!) }}</span>
                </div>
                <div v-if="detailsExpanded" class="mt-1 text-[11px] text-zinc-600 dark:text-zinc-500 leading-snug space-y-0.5">
                  <div v-if="tenantLabelFor(row.institutionId!)" class="truncate">
                    <span class="opacity-70">{{ $t('Padalinys') }}:</span>
                    <span class="ml-1">{{ tenantLabelFor(row.institutionId!) }}</span>
                  </div>
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

      <!-- Right: scrollable timeline -->
      <div ref="rightScroll" class="flex-1 overflow-auto min-w-0 h-full bg-white dark:bg-zinc-900" style="width: 0; min-width: 0;">
        <svg ref="svgEl" role="img" aria-label="Meetings timeline" class="block" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import * as d3 from 'd3'

import { Slider } from '@/Components/ui/slider'
import { getGanttColors, isDarkModeActive, type GanttColors } from './ganttColors'

/**
 * MeetingsGantt (d3)
 * - Rows = institutions; circles = meetings; bars = gaps.
 */

const props = withDefaults(defineProps<{
  meetings: Array<{ id: string | number, start_time: string | Date, institution_id: string | number, title?: string, institution?: string }>
  gaps: Array<{ institution_id: string | number, from: string | Date, until: string | Date, mode?: 'heads_up' | 'no_meetings', note?: string }>
  institutions?: Array<{ id: string | number, name?: string, tenant_id?: string | number }>
  daysBefore?: number
  daysAfter?: number
  dayWidth?: number
  startDate?: string | Date
  institutionsOrder?: Array<string | number>
  rowHeight?: number
  institutionNames?: Record<string | number, string>
  labelWidth?: number
  // Optional tenant categorization and filtering
  tenantFilter?: Array<string | number>
  institutionTenant?: Record<string | number, string | number>
  tenantNames?: Record<string | number, string>
  // Public meetings indicator lookup
  institutionHasPublicMeetings?: Record<string | number, boolean>
  // UI toggles
  showLegend?: boolean
  showTodayLine?: boolean
  interactive?: boolean
  showOnlyWithActivity?: boolean
  showOnlyWithPublicMeetings?: boolean
  // Row details/expansion (global multi-expand)
  detailsExpanded?: boolean
  expandedRowHeight?: number
  // Infinite scroll controls
  infiniteScroll?: boolean
  extendStepDays?: number
  extendThresholdPx?: number
  // Container height
  height?: string
}>(), {
  daysBefore: 30,
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
})

const wrap = ref<HTMLElement | null>(null)
const rightScroll = ref<HTMLElement | null>(null)
const leftLabels = ref<HTMLElement | null>(null)
const svgEl = ref<SVGSVGElement | null>(null)
let ro: ResizeObserver | null = null
const didInitialAutoScroll = ref(false)
const extending = ref(false)
const extraBefore = ref(0) // in days
const extraAfter = ref(0) // in days
const dayWidthPx = ref<number>(0)
const currentYear = ref<number | null>(null)
let curX: d3.ScaleTime<number, number> | null = null

const emit = defineEmits<{
  (e: 'create-meeting', payload: { institution_id: string | number, suggestedAt: Date }): void
  (e: 'fullscreen', payload: boolean): void
  (e: 'update:detailsExpanded', payload: boolean): void
  (e: 'show-legend-modal'): void
}>()

// Navigate to institution details (admin route helper if available)
const visitInstitution = (id: string | number) => {
  // @ts-ignore route helper might be globally available (ziggy)
  const routeFn = (window as any)?.route
  const url = routeFn ? routeFn('institutions.show', id) : `/admin/institutions/${id}`
  router.visit(url)
}

const parsedMeetings = computed(() => props.meetings.map(m => ({ ...m, date: new Date(m.start_time) })))
const parsedGaps = computed(() => props.gaps.map(g => ({ ...g, fromDate: new Date(g.from), untilDate: new Date(g.until) })))

// Active institution ids referenced by data
const activeInstitutionIds = computed(() => {
  const s = new Set<string | number>()
  parsedMeetings.value.forEach(m => s.add(m.institution_id))
  parsedGaps.value.forEach(g => s.add(g.institution_id))
  return s
})

// Base institution ids: explicit institutions prop + those referenced by data
const baseInstitutionIds = computed(() => {
  const ids = new Set<string | number>()
  if (props.institutions) props.institutions.forEach(i => ids.add(i.id))
  activeInstitutionIds.value.forEach(i => ids.add(i))
  return Array.from(ids)
})

// Final list of institution ids after filters and sorting
const institutions = computed(() => {
  const ids = new Set<string | number>()
  baseInstitutionIds.value.forEach(id => ids.add(id))
  let arr = Array.from(ids)
  // Filter by tenant if requested
  if (props.tenantFilter && props.tenantFilter.length && props.institutionTenant) {
    const filterSet = new Set(props.tenantFilter.map(v => String(v)))
    arr = arr.filter(id => filterSet.has(String((props.institutionTenant as any)[id as any])))
  }
  // Filter only those with activity if requested
  if (props.showOnlyWithActivity) {
    const act = activeInstitutionIds.value
    arr = arr.filter(id => act.has(id))
  }
  // Filter only those with public meetings if requested
  if (props.showOnlyWithPublicMeetings && props.institutionHasPublicMeetings) {
    const pubMap = props.institutionHasPublicMeetings
    arr = arr.filter(id => pubMap[id] || pubMap[String(id)])
  }
  if (props.institutionsOrder?.length) {
    const orderMap = new Map(props.institutionsOrder.map((id, idx) => [String(id), idx]))
    arr = arr.sort((a, b) => (orderMap.get(String(a)) ?? 1e9) - (orderMap.get(String(b)) ?? 1e9))
  } else {
    // Default: sort by institution name (fallback to id)
    const getName = (id: string | number) => {
      const fromProp = (props.institutionNames as any)?.[id as any]
      if (fromProp) return String(fromProp)
      const fromList = props.institutions?.find(i => i.id === id)?.name
      if (fromList) return String(fromList)
      const m = parsedMeetings.value.find(mm => mm.institution_id === id)
      return String(m?.institution ?? id)
    }
    arr = arr.sort((a, b) => getName(a).localeCompare(getName(b)))
  }
  return arr
})

const nameLookup = computed(() => {
  const map = new Map<string | number, string>()
  // prefer explicitly provided names
  if (props.institutionNames) {
    for (const [k, v] of Object.entries(props.institutionNames)) map.set(k, v)
  }
  // from explicit institutions list
  for (const i of (props.institutions ?? [])) {
    if (!map.has(i.id) && i.name) map.set(i.id, String(i.name))
  }
  // fallback from meetings payloads
  for (const m of props.meetings) {
    if (m.institution_id != null && m.institution && !map.has(m.institution_id)) {
      map.set(m.institution_id, m.institution)
    }
  }
  return map
})

// Merge tenant names from props with global page.props.tenants as fallback
const page = usePage()
const mergedTenantNames = computed<Record<string | number, string>>(() => {
  const result: Record<string | number, string> = {}
  // First, add from global tenants (as base)
  const globalTenants = (page.props.tenants as any[]) ?? []
  for (const tenant of globalTenants) {
    if (tenant?.id && tenant?.shortname) {
      result[tenant.id] = tenant.shortname
      result[String(tenant.id)] = tenant.shortname
    }
  }
  // Then, override with props.tenantNames (if provided)
  if (props.tenantNames) {
    for (const [k, v] of Object.entries(props.tenantNames)) {
      result[k] = v
    }
  }
  return result
})

const labelFor = (id: string | number) => nameLookup.value.get(id) ?? String(id)
const tenantFor = (id: string | number) => (props.institutionTenant as any)?.[id as any]
const tenantLabelFor = (id: string | number) => {
  const t = tenantFor(id)
  if (t == null) return undefined
  return mergedTenantNames.value[t as any]
}

type Row = { type: 'tenant'; key: string; tenantId: string | number } | { type: 'institution'; key: string | number; institutionId: string | number }
const rows = computed<Row[]>(() => {
  const ids = institutions.value
  if (props.institutionTenant && Object.keys(mergedTenantNames.value).length > 0) {
    const byTenant = new Map<string | number, Array<string | number>>()
    for (const id of ids) {
      const t = tenantFor(id) ?? 'unknown'
      const arr = byTenant.get(t) ?? []
      arr.push(id)
      byTenant.set(t, arr)
    }
    const tenantOrder = Array.from(byTenant.keys()).sort((a, b) => String(mergedTenantNames.value[a as any] ?? a).localeCompare(String(mergedTenantNames.value[b as any] ?? b)))
    const out: Row[] = []
    for (const t of tenantOrder) {
      out.push({ type: 'tenant', key: `__tenant__:${t}`, tenantId: t })
      for (const iid of byTenant.get(t) ?? []) out.push({ type: 'institution', key: iid, institutionId: iid })
    }
    return out
  }
  return ids.map(iid => ({ type: 'institution', key: iid, institutionId: iid } as Row))
})

// Layout with variable row heights (supports one expanded institution row)
interface LayoutRow { key: string | number; type: Row['type']; tenantId?: string | number; institutionId?: string | number; top: number; height: number }
const layoutRows = computed<LayoutRow[]>(() => {
  const out: LayoutRow[] = []
  let y = 0
  for (const r of rows.value) {
    const isInst = r.type === 'institution'
    const h = isInst
      ? (props.detailsExpanded ? (props.expandedRowHeight || props.rowHeight) : props.rowHeight)
      : props.rowHeight
    out.push({ key: r.key, type: r.type, tenantId: (r as any).tenantId, institutionId: (r as any).institutionId, top: y, height: h })
    y += h
  }
  return out
})

const heightByKey = computed(() => {
  const m = new Map<string | number, number>()
  for (const r of layoutRows.value) m.set(r.key, r.height)
  return m
})

const topByKey = computed(() => {
  const m = new Map<string | number, number>()
  for (const r of layoutRows.value) m.set(r.key, r.top)
  return m
})

const rowTop = (key: string | number) => topByKey.value.get(key) ?? 0
const rowHeightFor = (key: string | number) => heightByKey.value.get(key) ?? props.rowHeight
const rowCenter = (key: string | number) => rowTop(key) + rowHeightFor(key) / 2

// Container height should be larger than content to prevent overflow
const containerHeight = computed(() => {
  // For fullscreen mode with 100% height, don't set an explicit height
  // Let the h-full class and flex layout handle it
  if (props.height === '100%') {
    return undefined
  }

  // If height prop is provided (e.g., other specific values), use it
  if (props.height) {
    return props.height
  }

  // Otherwise calculate based on content
  const rowsH = layoutRows.value.reduce((acc, r) => acc + r.height, 0)
  const contentHeight = rowsH + 22 // header height
  // Add extra padding to prevent overflow when horizontal scrollbar appears
  const paddedHeight = contentHeight + 20
  return `${Math.max(200, paddedHeight)}px` // Ensure minimum height
})

// last meeting per institution (for label meta)
const lastMeetingByInstitution = computed(() => {
  const m = new Map<string | number, Date>()
  for (const it of parsedMeetings.value) {
    const cur = m.get(it.institution_id)
    if (!cur || it.date > cur) m.set(it.institution_id, it.date)
  }
  return m
})

const fmtDate = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' })
const fmtDateWithYear = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
const labelLast = (d: Date) => {
  const now = today()
  return d.getFullYear() === now.getFullYear() ? fmtDate.format(d) : fmtDateWithYear.format(d)
}

const today = () => {
  const d = new Date()
  d.setHours(0, 0, 0, 0)
  return d
}

// Import vacation configuration
import { getVacationPeriods } from '@/Pages/Admin/Dashboard/Components/vacationConfig';

const minTime = computed(() => {
  if (props.startDate) return d3.timeDay.offset(new Date(props.startDate), -extraBefore.value)
  return d3.timeDay.offset(today(), -(props.daysBefore + extraBefore.value))
})

const maxTime = computed(() => d3.timeDay.offset(today(), props.daysAfter + extraAfter.value))

// Margins: top matches the left labels header (22px). Bottom set to 0 so SVG
// height matches the left grid height exactly to avoid vertical overflow.
const margin = { top: 22, right: 8, bottom: 0, left: 8 }

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
  const container = wrap.value
  const svg = d3.select(svgEl.value)
  if (!container || svg.empty()) return

  // Get color palette based on current theme
  const colors = getGanttColors(isDarkModeActive())

  // derive width from current date span
  const totalDays = Math.max(1, d3.timeDay.count(minTime.value, maxTime.value))
  const viewportW = (rightScroll.value?.clientWidth ?? container.clientWidth) || 800
  const innerW = totalDays * (dayWidthPx.value || props.dayWidth)
  const rowsH = layoutRows.value.reduce((acc, r) => acc + r.height, 0)

  // Calculate the ideal content height (rows + top axis header)
  const idealHeight = rowsH + margin.top

  // Use the ideal height directly - the container will handle overflow
  const height = Math.max(margin.top + 50, idealHeight) // Ensure minimum height

  svg.attr('width', innerW).attr('height', height)
  svg.selectAll('*').remove()

  const innerWidth = innerW - margin.left - margin.right
  const innerH = height - margin.top - margin.bottom

  const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`)

  // custom tooltip for create affordance (separate from meeting tooltip)
  const createTip = d3.select(container)
    .selectAll('.gantt-tooltip-create')
    .data([null])
    .join('div')
    .attr('class', `gantt-tooltip-create pointer-events-none absolute z-10 hidden text-[11px] shadow-sm ring-1 rounded px-2 py-1`)
    .style('background', colors.tooltipBg)
    .style('color', colors.tooltipText)
    .style('--tw-ring-color', colors.tooltipBorder)

  // gradients
  const defs = svg.append('defs')
  const safeGrad = defs.append('linearGradient')
    .attr('id', 'safeBand')
    .attr('x1', '0%').attr('x2', '100%')
    .attr('y1', '0%').attr('y2', '0%')
  safeGrad.append('stop').attr('offset', '0%').attr('stop-color', colors.safetyBandStart)
  safeGrad.append('stop').attr('offset', '40%').attr('stop-color', colors.safetyBandMid)
  safeGrad.append('stop').attr('offset', '60%').attr('stop-color', colors.safetyBandMid)
  safeGrad.append('stop').attr('offset', '100%').attr('stop-color', colors.safetyBandEnd)

  const x = d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth])
  curX = x
  // Variable-row layout handled manually via rowTop/rowHeightFor — no band scale

  // zebra row background + hoverable rows
  const rowBg = g.append('g')
    .selectAll('rect')
    .data(layoutRows.value.map((r, i) => ({ r, i })))
    .enter()
    .append('rect')
    .attr('x', 0)
    .attr('y', d => rowTop(d.r.key))
    .attr('width', innerWidth)
    .attr('height', d => d.r.height)
    .attr('fill', d => {
      if (d.r.type === 'tenant') {
        return colors.tenantRow
      }
      return d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd
    })
    .attr('class', 'transition-colors duration-150')
    .on('mouseenter', function (event, d: any) { if (d.r.type !== 'tenant') d3.select(this).attr('fill', colors.rowHover) })
    .on('mouseleave', function (event, d: any) { if (d.r.type !== 'tenant') d3.select(this).attr('fill', (d.i % 2 === 0 ? colors.zebraEven : colors.zebraOdd)) })

  // background day bands + gridlines (excluding tenant rows)
  const days = d3.timeDay.range(minTime.value, maxTime.value)
  const institutionRows = layoutRows.value.filter(r => r.type === 'institution')

  // Create day bands only for institution rows (skip tenant rows)
  const dayBands = []
  for (const day of days) {
    if (day.getDay() === 1) { // Monday
      for (const row of institutionRows) {
        dayBands.push({ day, row })
      }
    }
  }

  g.append('g')
    .selectAll('rect')
    .data(dayBands)
    .enter()
    .append('rect')
    .attr('x', d => x(d.day))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => x(d3.timeDay.offset(d.day, 1)) - x(d.day))
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', colors.gridLine)

  /**
   * Vacation period bands (subtle background overlays)
   *
   * Renders semi-transparent colored bands across institution rows for vacation
   * periods (summer, winter, easter). These provide visual context for when
   * meetings typically don't occur.
   *
   * Color scheme:
   * - Summer: Amber (warm)
   * - Winter: Blue (cold)
   * - Easter: Violet (spring)
   *
   * Vacation periods are calculated dynamically from vacationConfig.ts based
   * on the current date range. Each band spans the full height of institution
   * rows but not tenant header rows.
   */
  const vacationPeriods = getVacationPeriods(minTime.value, maxTime.value)
  const vacationBands = []
  for (const period of vacationPeriods) {
    for (const row of institutionRows) {
      vacationBands.push({ period, row })
    }
  }

  g.append('g')
    .selectAll('rect.vacation-band')
    .data(vacationBands)
    .enter()
    .append('rect')
    .attr('class', 'vacation-band')
    .attr('x', d => Math.max(0, x(d.period.start)))
    .attr('y', d => rowTop(d.row.key))
    .attr('width', d => {
      const startX = Math.max(0, x(d.period.start))
      const endX = Math.min(innerWidth, x(d.period.end))
      return Math.max(0, endX - startX)
    })
    .attr('height', d => rowHeightFor(d.row.key))
    .attr('fill', d => {
      switch (d.period.type) {
        case 'summer': return colors.vacationSummer
        case 'winter': return colors.vacationWinter
        case 'easter': return colors.vacationEaster
        default: return colors.vacationDefault
      }
    })
    .attr('pointer-events', 'none')
    .append('title')
    .text(d => {
      const typeLabel = d.period.type === 'summer' ? 'Summer vacation' :
        d.period.type === 'winter' ? 'Winter vacation' :
          d.period.type === 'easter' ? 'Easter vacation' : 'Vacation'
      return `${typeLabel}: ${d.period.start.toLocaleDateString()} - ${d.period.end.toLocaleDateString()}`
    })

  // x axis ticks (weekly)
  const xAxis = d3.axisTop<Date>(x).ticks(d3.timeWeek.every(1)).tickFormat(d3.timeFormat('%b %d') as any)
  g.append('g').attr('transform', 'translate(0,0)').attr('class', 'text-[10px]').call(xAxis as any)

  // year markers: vertical line at Jan 1 and label the year above axis
  const yStart = d3.timeYear.floor(minTime.value)
  const yEnd = d3.timeYear.ceil(maxTime.value)
  const years = d3.timeYear.range(yStart, yEnd)
  // lines
  g.append('g')
    .selectAll('line.year-marker')
    .data(years)
    .enter()
    .append('line')
    .attr('class', 'year-marker')
    .attr('x1', d => x(d))
    .attr('x2', d => x(d))
    .attr('y1', 0)
    .attr('y2', innerH)
    .attr('stroke', colors.yearMarker)
    .attr('stroke-dasharray', '4,3')
    .attr('pointer-events', 'none')
  // labels
  const yearFmt = d3.timeFormat('%Y')
  g.append('g')
    .selectAll('text.year-label')
    .data(years)
    .enter()
    .append('text')
    .attr('class', 'year-label')
    .attr('x', d => x(d) + 4)
    .attr('y', -6)
    .attr('fill', colors.axisText)
    .style('font-size', '11px')
    .text(d => yearFmt(d))

  // row guides (subtle)
  g.append('g')
    .selectAll('line')
    .data(layoutRows.value)
    .enter()
    .append('line')
    .attr('x1', 0)
    .attr('x2', innerWidth)
    .attr('y1', d => rowTop(d.key) + d.height)
    .attr('y2', d => rowTop(d.key) + d.height)
    .attr('stroke', colors.gridLine)

  // green bands around meetings (±14 days) — sits above backgrounds, below gaps/dots
  const bandGroup = g.append('g')
  const bandDays = 14
  bandGroup
    .selectAll('rect')
    .data(parsedMeetings.value)
    .enter()
    .append('rect')
    .attr('x', d => x(d3.timeDay.offset(d.date, -bandDays)))
    .attr('y', d => rowTop(d.institution_id))
    .attr('width', d => Math.max(2, x(d3.timeDay.offset(d.date, bandDays)) - x(d3.timeDay.offset(d.date, -bandDays))))
    .attr('height', d => rowHeightFor(d.institution_id))
    .attr('fill', 'url(#safeBand)')
    .attr('stroke', 'transparent')

  // gaps (check-ins) as stroked lines - single style for all
  const gapGroup = g.append('g')

  gapGroup
    .selectAll('line')
    .data(parsedGaps.value)
    .enter()
    .append('line')
    .attr('x1', d => x(d.fromDate))
    .attr('x2', d => x(d.untilDate))
    .attr('y1', d => rowCenter(d.institution_id))
    .attr('y2', d => rowCenter(d.institution_id))
    .attr('stroke-width', 3)
    .attr('stroke-linecap', 'round')
    .attr('stroke', colors.gap)
    .style('cursor', 'pointer')
    .on('click', (event, d: any) => {
      // Emit a suggested create-meeting action for this institution at the start of gap
      emit('create-meeting', { institution_id: d.institution_id, suggestedAt: d.fromDate })
    })
    .append('title')
    .text(d => {
      const dateRange = `${d.fromDate.toLocaleDateString()} → ${d.untilDate.toLocaleDateString()}`
      return d.note ? `${d.note}\n${dateRange}` : `Check-in: ${dateRange}`
    })

  // meetings as dots
  const dotGroup = g.append('g')
  const dots = dotGroup
    .selectAll('circle')
    .data(parsedMeetings.value)
    .enter()
    .append('circle')
    .attr('cx', d => x(d.date))
    .attr('cy', d => rowCenter(d.institution_id))
    .attr('r', 3)
    .attr('fill', (d: any) => {
      // Determine fill color based on completion status
      if (d.completion_status === 'complete') {
        return colors.meetingComplete
      } else if (d.completion_status === 'no_items') {
        return 'none'  // No fill for empty meetings
      } else {
        return colors.meetingIncomplete
      }
    })
    .attr('stroke', (d: any) => {
      // Add stroke for no_items case to create outline
      if (d.completion_status === 'no_items') {
        return colors.meetingNoItems
      }
      return 'none'
    })
    .attr('stroke-width', (d: any) => {
      return d.completion_status === 'no_items' ? 1.5 : 0;
    })
    .attr('tabindex', 0)
    .style('cursor', 'pointer')
    .on('click', (event, d: any) => {
      const routeFn = (window as any)?.route
      const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
      router.visit(url)
    })
    .on('keydown', (event: KeyboardEvent, d: any) => {
      if (event.key === 'Enter' || event.key === ' ') {
        const routeFn = (window as any)?.route
        const url = routeFn ? routeFn('meetings.show', d.id) : `/admin/meetings/${d.id}`
        router.visit(url)
      }
    })
    .on('mouseenter', function () { d3.select(this).attr('r', 4) })
    .on('mouseleave', function () { d3.select(this).attr('r', 3) })

  dots.append('title').text((d: any) => {
    let status = '';
    if (d.completion_status === 'complete') {
      status = ' ✓';
    } else if (d.completion_status === 'no_items') {
      status = ' (no agenda items)';
    } else {
      status = ' (incomplete)';
    }
    return (d.title || labelFor(d.institution_id) || new Date(d.date).toLocaleString()) + status;
  })

  // custom tooltip for meetings
  const tipDiv = d3.select(container)
    .selectAll('.gantt-tooltip')
    .data([null])
    .join('div')
    .attr('class', `gantt-tooltip pointer-events-none absolute z-10 hidden text-[11px] shadow-sm ring-1 rounded px-2 py-1`)
    .style('background', colors.tooltipBg)
    .style('color', colors.tooltipText)
    .style('--tw-ring-color', colors.tooltipBorder)

  const fmt = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' })
  dots
    .on('mouseenter', (event, d: any) => {
      const rect = (container as HTMLElement).getBoundingClientRect()
      const name = labelFor(d.institution_id)
      const html = `<div class="font-medium text-[12px] leading-tight">${d.title ?? name}</div><div class="opacity-80">${fmt.format(d.date)}</div><div class="opacity-70">${name}</div>`
      tipDiv.html(html).classed('hidden', false)
      tipDiv.style('left', `${event.clientX - rect.left + 8}px`).style('top', `${event.clientY - rect.top + 8}px`)
    })
    .on('mousemove', (event) => {
      const rect = (container as HTMLElement).getBoundingClientRect()
      tipDiv.style('left', `${event.clientX - rect.left + 8}px`).style('top', `${event.clientY - rect.top + 8}px`)
    })
    .on('mouseleave', () => { tipDiv.classed('hidden', true) })

  // today line
  const t = new Date()
  if (props.showTodayLine && t >= minTime.value && t <= maxTime.value) {
    g.append('line')
      .attr('x1', x(t))
      .attr('x2', x(t))
      .attr('y1', 0)
      .attr('y2', innerH)
      .attr('stroke', colors.todayLine)
      .attr('stroke-width', 1)
      .attr('pointer-events', 'none')
  }

  // hover indicator line across the chart (full-height)
  const hoverLine = g.append('line')
    .attr('y1', 0)
    .attr('y2', innerH)
    .attr('stroke', colors.hoverLine)
    .attr('stroke-dasharray', '2,2')
    .attr('opacity', 0)
    .attr('pointer-events', 'none')

  // dashed circle affordance for creating a meeting on hovered row/day
  const createCircle = g.append('circle')
    .attr('r', 7)
    .attr('fill', 'none')
    .attr('stroke', colors.hoverCircle)
    .attr('stroke-dasharray', '4,3')
    .attr('opacity', 0)
    .attr('pointer-events', 'none')

  // Index meetings by row for snapping
  const meetingsByRow = new Map<string | number, { x: number; d: any }[]>()
  for (const m of parsedMeetings.value) {
    const k = m.institution_id
    const arr = meetingsByRow.get(k) ?? []
    arr.push({ x: x(m.date), d: m })
    meetingsByRow.set(k, arr)
  }
  for (const [k, arr] of meetingsByRow) arr.sort((a, b) => a.x - b.x)

  // Index gaps (check-ins) by institution for quick lookup
  const gapsByRow = new Map<string | number, Array<{ from: Date; until: Date; note?: string }>>()
  for (const g of parsedGaps.value) {
    const k = g.institution_id
    const arr = gapsByRow.get(k) ?? []
    arr.push({ from: g.fromDate, until: g.untilDate, note: g.note })
    gapsByRow.set(k, arr)
  }

  // Helper to find active gap for a given institution and date
  const findActiveGap = (institutionId: string | number, date: Date) => {
    const gaps = gapsByRow.get(institutionId) ?? []
    return gaps.find(gap => date >= gap.from && date <= gap.until)
  }

  // single mousemove handler: snap to nearest meeting dot in row (within threshold), else center of day
  g.on('mousemove', function (event) {
    const [mx, my] = d3.pointer(event, this as any)
    const dayStart = d3.timeDay.floor(x.invert(mx))
    if (my >= 0 && my <= innerH && rows.value.length) {
      // Find row by Y position (supports variable heights)
      let rowObj: Row | undefined
      for (const lr of layoutRows.value) {
        if (my >= lr.top && my < lr.top + lr.height) {
          rowObj = (lr.type === 'tenant' ? { type: 'tenant', key: lr.key, tenantId: lr.tenantId } as any : { type: 'institution', key: lr.key, institutionId: lr.institutionId } as any)
          break
        }
      }
      if (!rowObj) return
      if (rowObj.type === 'tenant') {
        createCircle.attr('opacity', 0)
        createTip.classed('hidden', true)
        hoverLine.attr('opacity', 0)
        return
      }
      const rowId = rowObj.institutionId
      const rowTopY = rowTop(rowObj.key)
      const rowBottom = rowTopY + rowHeightFor(rowObj.key)
      const dayEnd = d3.timeDay.offset(dayStart, 1)
      let centerX = (x(dayStart) + x(dayEnd)) / 2
      let circleR = 7
      const rowMeetings = meetingsByRow.get(rowId) || []
      if (rowMeetings.length) {
        /**
         * Binary search to find the nearest meeting to the mouse position
         * 
         * Uses a binary search algorithm to efficiently find the meeting
         * closest to the current mouse x-coordinate in O(log n) time.
         * 
         * Algorithm:
         * 1. Perform binary search to find the meeting at or after mouse position
         * 2. Check both the found meeting and the one before it
         * 3. Select the meeting closest to the mouse x-coordinate
         * 4. If within snap threshold (8px), snap to that meeting
         * 
         * @complexity O(log n) where n is the number of meetings in the row
         */
        let lo = 0, hi = rowMeetings.length - 1
        while (lo < hi) {
          const mid = Math.floor((lo + hi) / 2)
          const midVal = rowMeetings[mid]
          if (!midVal) break
          if (midVal.x < mx) lo = mid + 1
          else hi = mid
        }
        const candA = rowMeetings[lo]
        const candB = rowMeetings[Math.max(0, lo - 1)]
        const candidates = [candA, candB].filter((c): c is { x: number; d: any } => !!c)
        let best = candidates.length ? candidates[0] : undefined
        if (candidates.length > 1 && candidates[0] && candidates[1]) {
          if (Math.abs(candidates[1].x - mx) < Math.abs(candidates[0].x - mx)) {
            best = candidates[1]
          }
        }
        const dist = best ? Math.abs(best.x - mx) : Infinity
        const snapThreshold = 8
        if (best && dist <= snapThreshold) {
          centerX = best.x
          circleR = 4
        }
      }

      hoverLine
        .attr('x1', centerX)
        .attr('x2', centerX)
        .attr('y1', 0)
        .attr('y2', innerH)
        .attr('opacity', 1)

      // Show create affordance only when interactive
      createCircle
        .attr('cx', centerX)
        .attr('cy', rowCenter(rowObj.key))
        .attr('r', circleR)
        .attr('opacity', props.interactive ? 1 : 0)

      const rect = (container as HTMLElement).getBoundingClientRect()
      
      // Build tooltip HTML with optional check-in info
      const activeGap = findActiveGap(rowId, dayStart)
      let html = `<div class="font-medium text-[12px] leading-tight">${labelFor(rowId)}</div><div class="opacity-80">${fmtDateWithYear.format(dayStart)}</div>`
      if (activeGap) {
        const gapDateRange = `${fmtDate.format(activeGap.from)} → ${fmtDate.format(activeGap.until)}`
        html += `<div class="mt-1.5 pt-1.5 border-t border-current/20">`
        html += `<div class="text-[10px] uppercase tracking-wide opacity-60 mb-0.5">Check-in</div>`
        if (activeGap.note) {
          html += `<div class="line-clamp-2 text-[11px]">${activeGap.note}</div>`
        }
        html += `<div class="opacity-70 text-[10px]">${gapDateRange}</div>`
        html += `</div>`
      }
      
      if (props.interactive) {
        createTip.html(html).classed('hidden', false)
        createTip.style('left', `${event.clientX - rect.left + 8}px`).style('top', `${event.clientY - rect.top + 8}px`)
      }
    } else {
      createCircle.attr('opacity', 0)
      createTip.classed('hidden', true)
      hoverLine.attr('opacity', 0)
    }
  })

  g.on('mouseleave', () => {
    hoverLine.attr('opacity', 0)
    createCircle.attr('opacity', 0)
    createTip.classed('hidden', true)
  })

  // click-to-create only when not clicking on dots or gaps
  g.on('click', function (event) {
    const target = event.target as Node
    if ((dotGroup.node() && dotGroup.node()!.contains(target)) || (gapGroup.node() && gapGroup.node()!.contains(target))) {
      return
    }
    const [mx, my] = d3.pointer(event, this as any)
    if (my < 0 || my > innerH || !layoutRows.value.length) return
    const day = d3.timeDay.floor(x.invert(mx))
    let rowObj: Row | undefined
    for (const lr of layoutRows.value) {
      if (my >= lr.top && my < lr.top + lr.height) {
        rowObj = (lr.type === 'tenant' ? { type: 'tenant', key: lr.key, tenantId: lr.tenantId } as any : { type: 'institution', key: lr.key, institutionId: lr.institutionId } as any)
        break
      }
    }
    if (!rowObj) return
    if (rowObj.type === 'institution' && props.interactive) emit('create-meeting', { institution_id: rowObj.institutionId, suggestedAt: day })
  })

  // auto-scroll to today's position
  const left = x(new Date())
  if (rightScroll.value && !didInitialAutoScroll.value) {
    rightScroll.value.scrollLeft = Math.max(0, left - 8)
    didInitialAutoScroll.value = true
  }
  // update current year badge from center of viewport
  const el = rightScroll.value
  if (el && curX) {
    const center = el.scrollLeft + el.clientWidth / 2
    const d = curX.invert(center)
    currentYear.value = d.getFullYear()
  }
} // end render

onMounted(() => {
  dayWidthPx.value = props.dayWidth
  render()
  ro = new ResizeObserver(() => render())
  if (wrap.value) ro.observe(wrap.value)

  // Synchronize vertical scrolling between labels and timeline
  const timelineScroll = rightScroll.value
  const labelsContainer = leftLabels.value

  if (timelineScroll && labelsContainer) {
    let isSyncing = false

    const syncVerticalScroll = () => {
      if (isSyncing) return
      isSyncing = true

      const { scrollTop } = timelineScroll
      
      // In fullscreen mode (overflow-y-auto on labels), sync scrollTop directly
      // In normal mode (overflow-hidden on labels), use transform
      if (props.height === '100%') {
        labelsContainer.scrollTop = scrollTop
      } else {
        const labelsContent = labelsContainer.querySelector('.grid') as HTMLElement | null
        if (labelsContent) {
          labelsContent.style.transform = `translateY(-${scrollTop}px)`
        }
      }

      requestAnimationFrame(() => { isSyncing = false })
    }

    const syncFromLabels = () => {
      if (isSyncing || props.height !== '100%') return
      isSyncing = true
      timelineScroll.scrollTop = labelsContainer.scrollTop
      requestAnimationFrame(() => { isSyncing = false })
    }

    timelineScroll.addEventListener('scroll', syncVerticalScroll, { passive: true })
    labelsContainer.addEventListener('scroll', syncFromLabels, { passive: true })
  }

  // attach infinite scroll handler
  if (timelineScroll && props.infiniteScroll) {
    timelineScroll.addEventListener('scroll', onScroll, { passive: true })
  }
})

onUnmounted(() => {
  ro?.disconnect()
  const el = rightScroll.value
  if (el) el.removeEventListener('scroll', onScroll as any)
})

watch([parsedMeetings, parsedGaps, institutions, rows, () => props.daysBefore, () => props.daysAfter, () => props.startDate, () => props.tenantFilter, () => props.showOnlyWithActivity, () => props.showOnlyWithPublicMeetings, () => props.detailsExpanded, extraBefore, extraAfter, dayWidthPx], () => render())

/**
 * Infinite scroll handler that extends the date range dynamically
 *
 * Monitors horizontal scroll position and extends the timeline when the user
 * scrolls near the edges:
 * - **Left edge**: Adds days to the past (extraBefore), preserves viewport by
 *   adjusting scrollLeft to compensate for the added width
 * - **Right edge**: Adds days to the future (extraAfter), viewport naturally
 *   stays in place
 *
 * This enables seamless exploration of the timeline without loading all data
 * upfront. The `extending` flag prevents concurrent extensions.
 *
 * @see props.infiniteScroll - Enable/disable infinite scroll
 * @see props.extendThresholdPx - Distance from edge to trigger extension (default: 200px)
 * @see props.extendStepDays - Number of days to add on each extension (default: 30)
 */
function onScroll() {
  if (!props.infiniteScroll) return
  const el = rightScroll.value
  if (!el || extending.value) return
  const threshold = props.extendThresholdPx ?? 200
  const stepDays = props.extendStepDays ?? 30
  const atLeft = el.scrollLeft <= threshold
  const atRight = (el.scrollLeft + el.clientWidth) >= (el.scrollWidth - threshold)
  if (atLeft) {
    extending.value = true
    const prev = el.scrollLeft
    extraBefore.value += stepDays
    nextTick(() => {
      // preserve viewport by shifting right by added width
      el.scrollLeft = prev + stepDays * (dayWidthPx.value || props.dayWidth)
      extending.value = false
    })
  } else if (atRight) {
    extending.value = true
    extraAfter.value += stepDays
    nextTick(() => { extending.value = false })
  }
}

/**
 * Zoom/scale handler that adjusts day width while preserving viewport center
 *
 * When the user adjusts the scale slider, this function:
 * 1. Captures the date at the center of the current viewport (anchor date)
 * 2. Updates the day width (zoom level)
 * 3. Re-renders the chart with the new scale
 * 4. Adjusts scrollLeft so the same anchor date remains at the viewport center
 *
 * This creates a smooth zoom experience where the user's focus point stays
 * stable, similar to zooming in map applications.
 *
 * @param values - Array containing the new day width from the slider component
 *
 * @example
 * // User drags slider from 24px to 48px per day
 * // The date at viewport center (e.g., March 15) stays centered
 * onScaleChange([48])
 */
function onScaleChange(values?: number[]) {
  const newWidth = Math.max(4, Math.min(96, values?.[0] ?? props.dayWidth))
  const el = rightScroll.value
  let anchorDate: Date | null = null
  let centerOffset = 0
  if (el && curX) {
    centerOffset = el.clientWidth / 2
    const center = el.scrollLeft + centerOffset
    anchorDate = curX.invert(center)
  }
  dayWidthPx.value = newWidth
  nextTick(() => {
    if (el && curX && anchorDate) {
      // curX updated by render watcher
      const newX = curX as d3.ScaleTime<number, number>
      const target = newX(anchorDate as Date) - centerOffset
      el.scrollLeft = Math.max(0, target)
    }
  })
}
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
