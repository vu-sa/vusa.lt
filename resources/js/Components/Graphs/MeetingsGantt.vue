<template>
  <div ref="wrap" class="relative w-full max-w-full outline-none" tabindex="0" :class="{ 'h-full flex flex-col': props.height === '100%' }">
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
          <Slider :min="3" :max="36" :step="1" :model-value="[dayWidthPx || dayWidth]"
            @update:model-value="onScaleChange" />
        </div>
        <!-- Current year badge with navigation dropdown -->
        <DropdownMenu v-if="currentYear">
          <DropdownMenuTrigger as-child>
            <button type="button"
              class="px-2 py-0.5 rounded border text-xs text-zinc-700 dark:text-zinc-300 bg-white/70 dark:bg-zinc-800/70 backdrop-blur border-zinc-200 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer transition-colors flex items-center gap-1">
              {{ currentYear }}
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 opacity-60" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="min-w-[160px]">
            <DropdownMenuLabel class="text-xs">{{ $t('Eiti į metus') }}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="navigateYears(-3)" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M15.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M9.707 15.707a1 1 0 01-1.414 0l-5-5a1 1 0 010-1.414l5-5a1 1 0 111.414 1.414L5.414 10l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
              </svg>
              <span>-3 {{ $t('metai') }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem @click="navigateYears(-1)" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>-1 {{ $t('metai') }}</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="setCenterDate(null); navigateToToday()" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <circle cx="10" cy="10" r="3" />
              </svg>
              <span>{{ $t('Šiandien') }}</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem @click="navigateYears(1)" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
              <span>+1 {{ $t('metai') }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem @click="navigateYears(3)" class="cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 15.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
              <span>+3 {{ $t('metai') }}</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
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
      <div ref="leftLabels" class="shrink-0 bg-white dark:bg-zinc-900 z-[35]"
        :class="props.height === '100%' ? 'overflow-y-auto overflow-x-hidden' : 'overflow-hidden'"
        :style="{ width: `${labelWidth}px` }"
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
              class="px-3 py-1 text-sm text-zinc-700 dark:text-zinc-300 border-b border-zinc-100 dark:border-zinc-800 flex items-start gap-2 truncate"
              :class="[idx % 2 === 0 ? 'bg-zinc-50/40 dark:bg-zinc-800/30' : '']" :title="labelFor(row.institutionId!)">
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between gap-2">
                  <div class="flex items-center gap-1.5 min-w-0">
                    <button type="button"
                      class="truncate text-left hover:underline cursor-pointer focus:underline focus:outline-none"
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
                      <circle cx="12" cy="12" r="10"/>
                      <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                      <path d="M2 12h20"/>
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
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import * as d3 from 'd3'

import { Slider } from '@/Components/ui/slider'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'
import { getGanttColors, isDarkModeActive, type GanttColors } from './ganttColors'
import { useGanttSettings } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings'
import { useGanttInteractions } from './composables/useGanttInteractions'
import { useGanttViewport } from './composables/useGanttViewport'

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
  createGanttTooltip,
  createCenterLine,
  type GanttTooltipManager,
  type CenterLineManager,
} from './renderers'

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
  // Duty members display
  dutyMembers?: Array<{ institution_id: string | number, user: { id: string, name: string, profile_photo_path?: string | null }, start_date: string | Date, end_date?: string | Date | null }>
  inactivePeriods?: Array<{ institution_id: string | number, from: string | Date, until: string | Date }>
  showDutyMembers?: boolean
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
})

const wrap = ref<HTMLElement | null>(null)
const rightScroll = ref<HTMLElement | null>(null)
const axisScroll = ref<HTMLElement | null>(null)
const leftLabels = ref<HTMLElement | null>(null)
const svgEl = ref<SVGSVGElement | null>(null)
const axisEl = ref<SVGSVGElement | null>(null)
let ro: ResizeObserver | null = null
// curX as ref so it can be passed to composables
const curXRef = ref<d3.ScaleTime<number, number> | null>(null)
// Center line manager for scroll updates
let centerLineManager: CenterLineManager | null = null

// Use injected Gantt settings (eliminates prop drilling for dayWidth, etc.)
// Falls back to local settings if no provider is found (standalone usage)
const ganttSettings = useGanttSettings()
const dayWidthPx = ganttSettings.dayWidthPx
const centerDateTimestamp = ganttSettings.centerDateTimestamp
const setCenterDate = ganttSettings.setCenterDate

const emit = defineEmits<{
  (e: 'create-meeting', payload: { institution_id: string | number, suggestedAt: Date }): void
  (e: 'fullscreen', payload: boolean): void
  (e: 'update:detailsExpanded', payload: boolean): void
  (e: 'show-legend-modal'): void
}>()

// Navigate to institution details (admin route helper if available)
const visitInstitution = (id: string | number, event?: MouseEvent | KeyboardEvent) => {
  // @ts-ignore route helper might be globally available (ziggy)
  const routeFn = (window as any)?.route
  const url = routeFn ? routeFn('institutions.show', id) : `/admin/institutions/${id}`
  // Support Ctrl/Cmd+click to open in new tab
  if (event && (event.ctrlKey || event.metaKey || (event instanceof MouseEvent && event.button === 1))) {
    window.open(url, '_blank')
  } else {
    router.visit(url)
  }
}

const parsedMeetings = computed(() => props.meetings.map(m => ({ ...m, date: new Date(m.start_time) })))
const parsedGaps = computed(() => props.gaps.map(g => ({ ...g, fromDate: new Date(g.from), untilDate: new Date(g.until) })))

// Parsed duty members with Date objects
const parsedDutyMembers = computed(() => (props.dutyMembers ?? []).map(m => ({
  ...m,
  startDate: new Date(m.start_date),
  endDate: m.end_date ? new Date(m.end_date) : null
})))

// Parsed inactive periods with Date objects
const parsedInactivePeriods = computed(() => (props.inactivePeriods ?? []).map(p => ({
  ...p,
  fromDate: new Date(p.from),
  untilDate: new Date(p.until)
})))

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

// Filtered meetings based on currently visible institutions
const filteredMeetings = computed(() => {
  const visibleIds = new Set(institutions.value)
  return parsedMeetings.value.filter(m => visibleIds.has(m.institution_id))
})

// Filtered gaps based on currently visible institutions
const filteredGaps = computed(() => {
  const visibleIds = new Set(institutions.value)
  return parsedGaps.value.filter(g => visibleIds.has(g.institution_id))
})

// Filtered duty members based on currently visible institutions (when showDutyMembers is true)
const filteredDutyMembers = computed(() => {
  if (!props.showDutyMembers) return []
  const visibleIds = new Set(institutions.value.map(String))
  return parsedDutyMembers.value.filter(m => visibleIds.has(String(m.institution_id)))
})

// Filtered inactive periods based on currently visible institutions (when showDutyMembers is true)
const filteredInactivePeriods = computed(() => {
  if (!props.showDutyMembers) return []
  const visibleIds = new Set(institutions.value.map(String))
  return parsedInactivePeriods.value.filter(p => visibleIds.has(String(p.institution_id)))
})

// Group duty members by institution and start date for avatar stacking
const groupedDutyMembers = computed(() => {
  const groups = new Map<string, typeof filteredDutyMembers.value>()
  for (const member of filteredDutyMembers.value) {
    // Group by institution + day (same day = same group)
    const dayKey = `${member.institution_id}:${member.startDate.toDateString()}`
    const arr = groups.get(dayKey) ?? []
    arr.push(member)
    groups.set(dayKey, arr)
  }
  return groups
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

// last meeting per institution (for label meta) - based on filtered meetings
const lastMeetingByInstitution = computed(() => {
  const m = new Map<string | number, Date>()
  for (const it of filteredMeetings.value) {
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

// Initialize interaction composable for scroll, zoom, and navigation
const interactions = useGanttInteractions(
  {
    daysBefore: props.daysBefore,
    daysAfter: props.daysAfter,
    infiniteScroll: props.infiniteScroll,
    extendThresholdPx: props.extendThresholdPx ?? 200,
    extendStepDays: props.extendStepDays ?? 30,
    startDate: props.startDate ? new Date(props.startDate) : null,
    centerDateTimestamp: centerDateTimestamp, // Pass ref, not value
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
  }
)

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
} = interactions

// Initialize viewport composable for horizontal culling (performance optimization)
const viewport = useGanttViewport(rightScroll, curXRef, { bufferPx: 300 })

// Create viewport-culled data for rendering
const visibleMeetings = viewport.createVisibleMeetings(filteredMeetings)
const visibleGaps = viewport.createVisibleGaps(filteredGaps)
const visibleDutyMembers = viewport.createVisibleDutyMembers(filteredDutyMembers)

// Margins: top is 0 since x-axis is now in a separate sticky SVG.
// Bottom set to 0 so SVG height matches the left grid height exactly.
const margin = { top: 0, right: 8, bottom: 0, left: 8 }
const axisHeight = 22 // Height of the sticky x-axis header

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
  const axisSvg = d3.select(axisEl.value)
  if (!container || svg.empty()) return

  // Get color palette based on current theme
  const colors = getGanttColors(isDarkModeActive())

  // derive width from current date span
  const totalDays = Math.max(1, d3.timeDay.count(minTime.value, maxTime.value))
  const viewportW = (rightScroll.value?.clientWidth ?? container.clientWidth) || 800
  const calculatedW = totalDays * (dayWidthPx.value || props.dayWidth)
  // Ensure minimum width slightly larger than viewport to guarantee horizontal scrollbar
  const innerW = Math.max(calculatedW, viewportW + 50)
  const rowsH = layoutRows.value.reduce((acc, r) => acc + r.height, 0)

  // Calculate the ideal content height (rows only, axis is separate)
  const idealHeight = rowsH

  // Use the ideal height directly - the container will handle overflow
  const height = Math.max(50, idealHeight) // Ensure minimum height

  svg.attr('width', innerW).attr('height', height)
  svg.selectAll('*').remove()
  
  // Also set axis SVG width to match
  if (!axisSvg.empty()) {
    axisSvg.attr('width', innerW).attr('height', axisHeight)
    axisSvg.selectAll('*').remove()
  }

  const innerWidth = innerW - margin.left - margin.right
  const innerH = height - margin.top - margin.bottom

  const g = svg.append('g').attr('transform', `translate(${margin.left},${margin.top})`)

  // gradients and patterns
  const defs = svg.append('defs')
  setupDefs({
    defs,
    colors,
    isDarkMode: isDarkModeActive(),
  })

  // Create unified tooltip manager for all renderers
  // Remove old tooltip elements first to prevent duplicates
  d3.select(container).selectAll('.gantt-tooltip, .gantt-tooltip-create, .gantt-tooltip-member, .gantt-unified-tooltip').remove()
  const tooltipManager = createGanttTooltip(container, colors)

  // Create or update center line indicator
  if (centerLineManager) {
    centerLineManager.destroy()
  }
  if (rightScroll.value) {
    const currentLocale = (page.props.app as any)?.locale ?? 'lt'
    centerLineManager = createCenterLine({
      container: container as HTMLElement,
      rightScroll: rightScroll.value,
      x: d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth]),
      colors,
      marginLeft: margin.left,
      axisHeight,
      locale: currentLocale,
      onNavigateToToday: () => {
        // Clear stored center date and navigate to today
        setCenterDate(null)
        navigateToToday()
      },
    })
  }

  const x = d3.scaleTime().domain([minTime.value, maxTime.value]).range([0, innerWidth])
  curXRef.value = x
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
  })

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
  })

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
    })
  }

  // Render sticky x-axis in separate SVG using extracted renderer
  const currentLocale = (page.props.app as any)?.locale ?? 'lt'
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
  })

  // gaps (check-ins) as stroked lines - using extracted renderer
  renderGaps({
    g,
    x,
    gaps: filteredGaps.value,
    colors,
    rowCenter,
    onCreateMeeting: (payload: { institution_id: string | number; suggestedAt: Date }) => emit('create-meeting', payload),
  })

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
  })

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
    })
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
  })

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
    onCreateMeeting: (payload) => emit('create-meeting', payload),
  })

  // Apply initial scroll position using the composable's function
  // This centers on the stored center date (from localStorage) or today
  applyInitialScrollPosition(x, margin.left)
  
  // Update current year badge from center of viewport
  updateCurrentYear()
} // end render

onMounted(() => {
  // Apply initial extension for low zoom levels BEFORE first render
  // This ensures the timeline has the correct range when we calculate initial scroll position
  applyInitialExtension()
  
  // Now render with correct extensions already applied
  render()
  ro = new ResizeObserver(() => render())
  if (wrap.value) ro.observe(wrap.value)

  // Watch for dark mode changes via MutationObserver on document.documentElement
  const themeObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.attributeName === 'class') {
        // Re-render when theme class changes
        render()
        break
      }
    }
  })
  themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })

  // Setup vertical scroll synchronization using composable
  const cleanupVerticalSync = setupVerticalScrollSync(props.height === '100%')
  
  // Attach infinite scroll handler using composable
  const cleanupScrollHandler = attachScrollHandler()
  
  // Attach viewport tracking for horizontal culling
  const cleanupViewport = viewport.attachViewportTracking()

  // Attach keyboard navigation handler
  const cleanupKeyboard = attachKeyboardHandler(wrap.value)

  // Setup center line scroll handler with debounced center date saving
  let saveCenterDateTimeout: ReturnType<typeof setTimeout> | null = null
  
  // Helper function to save current center date immediately
  const saveCurrentCenterDate = () => {
    if (rightScroll.value && curXRef.value) {
      const scrollLeft = rightScroll.value.scrollLeft
      const viewportWidth = rightScroll.value.clientWidth
      const xScalePosition = scrollLeft + viewportWidth / 2 - margin.left
      const centerDate = curXRef.value.invert(xScalePosition)
      setCenterDate(centerDate)
    }
  }
  
  const handleCenterLineScroll = () => {
    centerLineManager?.update()
    
    // Debounced save of center date to localStorage (200ms delay for faster persistence)
    if (saveCenterDateTimeout) clearTimeout(saveCenterDateTimeout)
    saveCenterDateTimeout = setTimeout(saveCurrentCenterDate, 200)
  }
  rightScroll.value?.addEventListener('scroll', handleCenterLineScroll, { passive: true })
  
  // Also save on beforeunload to catch any pending scroll position
  const handleBeforeUnload = () => {
    if (saveCenterDateTimeout) {
      clearTimeout(saveCenterDateTimeout)
    }
    saveCurrentCenterDate()
  }
  window.addEventListener('beforeunload', handleBeforeUnload)

  // Store cleanup functions for onUnmounted
  onUnmounted(() => {
    ro?.disconnect()
    themeObserver.disconnect()
    cleanupVerticalSync?.()
    cleanupScrollHandler?.()
    cleanupViewport?.()
    cleanupKeyboard?.()
    centerLineManager?.destroy()
    rightScroll.value?.removeEventListener('scroll', handleCenterLineScroll)
    window.removeEventListener('beforeunload', handleBeforeUnload)
    if (saveCenterDateTimeout) clearTimeout(saveCenterDateTimeout)
    // Save final position on unmount
    saveCurrentCenterDate()
  })
})

watch([parsedMeetings, parsedGaps, parsedDutyMembers, parsedInactivePeriods, institutions, rows, () => props.daysBefore, () => props.daysAfter, () => props.startDate, () => props.tenantFilter, () => props.showOnlyWithActivity, () => props.showOnlyWithPublicMeetings, () => props.showDutyMembers, () => props.detailsExpanded, extraBefore, extraAfter, dayWidthPx], () => render())
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
