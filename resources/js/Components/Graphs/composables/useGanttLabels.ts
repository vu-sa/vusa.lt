/**
 * useGanttLabels - Gantt chart label and name utilities
 * 
 * Extracted from MeetingsGantt.vue to reduce component complexity.
 * Handles:
 * - Institution name lookups from multiple sources
 * - Tenant name merging (props + global page props)
 * - Date formatting utilities
 * - Relationship tooltip generation
 */
import { computed, type ComputedRef } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'
import type { LayoutRow } from './useGanttLayout'

export interface GanttLabelsOptions {
  /** Institution names from props (getter for reactivity) */
  institutionNames: () => Record<string | number, string> | undefined
  /** Tenant names from props (getter for reactivity) */
  tenantNames: () => Record<string | number, string> | undefined
  /** Institution to tenant mapping (getter for reactivity) */
  institutionTenant: () => Record<string | number, string | number> | undefined
}

export interface GanttLabelsData {
  /** Institutions from props with name (getter for reactivity) */
  institutions: () => Array<{ id: string | number; name?: string }> | undefined
  /** Meetings for fallback name lookup (getter for reactivity) */
  meetings: () => Array<{ institution_id: string | number; institution?: string }>
  /** Filtered meetings for last meeting calculation */
  filteredMeetings: ComputedRef<Array<{ institution_id: string | number; date: Date }>>
}

export function useGanttLabels(
  options: GanttLabelsOptions,
  data: GanttLabelsData
) {
  const page = usePage()

  /**
   * Institution name lookup map
   * Priority: props.institutionNames > props.institutions > meeting.institution
   */
  const nameLookup = computed(() => {
    const map = new Map<string | number, string>()
    
    // Prefer explicitly provided names
    const names = options.institutionNames()
    if (names) {
      for (const [k, v] of Object.entries(names)) {
        map.set(k, v)
      }
    }
    
    // From explicit institutions list
    const inst = data.institutions()
    for (const i of (inst ?? [])) {
      if (!map.has(i.id) && i.name) {
        map.set(i.id, String(i.name))
      }
    }
    
    // Fallback from meetings payloads
    const meetings = data.meetings()
    for (const m of meetings) {
      if (m.institution_id != null && m.institution && !map.has(m.institution_id)) {
        map.set(m.institution_id, m.institution)
      }
    }
    
    return map
  })

  /**
   * Merged tenant names from props and global page.props.tenants
   */
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
    const tenantNames = options.tenantNames()
    if (tenantNames) {
      for (const [k, v] of Object.entries(tenantNames)) {
        result[k] = v
      }
    }
    
    return result
  })

  /**
   * Get institution name by ID
   */
  const labelFor = (id: string | number) => nameLookup.value.get(id) ?? String(id)

  /**
   * Get tenant ID for an institution
   */
  const tenantFor = (id: string | number) => {
    const mapping = options.institutionTenant()
    return mapping?.[id as keyof typeof mapping]
  }

  /**
   * Get tenant name for an institution
   */
  const tenantLabelFor = (id: string | number) => {
    const t = tenantFor(id)
    if (t == null) return undefined
    return mergedTenantNames.value[t]
  }

  /**
   * Last meeting date per institution
   */
  const lastMeetingByInstitution = computed(() => {
    const m = new Map<string | number, Date>()
    for (const it of data.filteredMeetings.value) {
      const cur = m.get(it.institution_id)
      if (!cur || it.date > cur) m.set(it.institution_id, it.date)
    }
    return m
  })

  // Date formatters
  const fmtDate = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' })
  const fmtDateWithYear = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' })

  /**
   * Format last meeting date (show year only if different from current)
   */
  const labelLast = (d: Date) => {
    const now = new Date()
    now.setHours(0, 0, 0, 0)
    return d.getFullYear() === now.getFullYear() ? fmtDate.format(d) : fmtDateWithYear.format(d)
  }

  /**
   * Generate tooltip text for related institution link icon
   */
  const getRelationshipTooltip = (row: LayoutRow): string => {
    if (!row.isRelated) return ''
    
    const sourceName = row.sourceInstitutionId 
      ? labelFor(row.sourceInstitutionId) 
      : $t('Nežinoma institucija')
    
    const accessText = row.authorized !== false 
      ? $t('relationships.tooltip_authorized')
      : $t('relationships.tooltip_not_authorized')
    
    return `${$t('relationships.tooltip_via')} ${sourceName}\n${accessText}`
  }

  return {
    nameLookup,
    mergedTenantNames,
    labelFor,
    tenantFor,
    tenantLabelFor,
    lastMeetingByInstitution,
    fmtDate,
    fmtDateWithYear,
    labelLast,
    getRelationshipTooltip,
  }
}
