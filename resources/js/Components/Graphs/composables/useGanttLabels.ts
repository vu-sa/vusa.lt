/** Names, date formats and relationship tooltips for the meetings Gantt's labels. */
import { computed, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { LayoutRow } from './useGanttLayout';

export interface GanttLabelsOptions {
  /** Institution names from props (getter for reactivity) */
  institutionNames: () => Record<string | number, string> | undefined;
  /** Tenant names from props (getter for reactivity) */
  tenantNames: () => Record<string | number, string> | undefined;
}

export interface GanttLabelsData {
  /** Institutions from props with name (getter for reactivity) */
  institutions: () => Array<{ id: string | number; name?: string }> | undefined;
  /** Filtered meetings for last meeting calculation */
  filteredMeetings: ComputedRef<Array<{ institution_id: string | number; date: Date }>>;
}

export function useGanttLabels(
  options: GanttLabelsOptions,
  data: GanttLabelsData,
) {
  const page = usePage();

  /** Explicit names first, then the institutions list. */
  const nameLookup = computed(() => {
    const map = new Map<string, string>();

    // Prefer explicitly provided names
    const names = options.institutionNames();
    if (names) {
      for (const [k, v] of Object.entries(names)) {
        map.set(k, v);
      }
    }

    // From explicit institutions list
    const inst = data.institutions();
    for (const i of (inst ?? [])) {
      if (!map.has(String(i.id)) && i.name) {
        map.set(String(i.id), String(i.name));
      }
    }

    return map;
  });

  /**
   * Merged tenant names from props and global page.props.tenants
   */
  const mergedTenantNames = computed<Record<string | number, string>>(() => {
    const result: Record<string | number, string> = {};

    // First, add from global tenants (as base)
    const globalTenants = (page.props.tenants as any[]) ?? [];
    for (const tenant of globalTenants) {
      if (tenant?.id && tenant?.shortname) {
        result[tenant.id] = tenant.shortname;
        result[String(tenant.id)] = tenant.shortname;
      }
    }

    // Then, override with props.tenantNames (if provided)
    const tenantNames = options.tenantNames();
    if (tenantNames) {
      for (const [k, v] of Object.entries(tenantNames)) {
        result[k] = v;
      }
    }

    return result;
  });

  /**
   * Get institution name by ID
   */
  const labelFor = (id: string | number) => nameLookup.value.get(String(id)) ?? String(id);

  /**
   * Last meeting date per institution
   */
  /** Latest past meeting per institution among the loaded ones; planned meetings are not "last". */
  const lastMeetingByInstitution = computed(() => {
    const now = new Date();
    const m = new Map<string, Date>();
    for (const it of data.filteredMeetings.value) {
      if (it.date > now) continue;
      const id = String(it.institution_id);
      const cur = m.get(id);
      if (!cur || it.date > cur) m.set(id, it.date);
    }
    return m;
  });

  // Date formatters
  const fmtDate = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric' });
  const fmtDateWithYear = new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' });

  /**
   * Format last meeting date (show year only if different from current)
   */
  const labelLast = (d: Date) => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    return d.getFullYear() === now.getFullYear() ? fmtDate.format(d) : fmtDateWithYear.format(d);
  };

  /**
   * Generate tooltip text for related institution link icon
   */
  const getRelationshipTooltip = (row: LayoutRow): string => {
    if (!row.isRelated) return '';

    const sourceName = row.sourceInstitutionId
      ? labelFor(row.sourceInstitutionId)
      : $t('Nežinoma institucija');

    const accessText = row.authorized !== false
      ? $t('relationships.tooltip_authorized')
      : $t('relationships.tooltip_not_authorized');

    return `${$t('relationships.tooltip_via')} ${sourceName}\n${accessText}`;
  };

  return {
    nameLookup,
    mergedTenantNames,
    labelFor,
    lastMeetingByInstitution,
    fmtDate,
    fmtDateWithYear,
    labelLast,
    getRelationshipTooltip,
  };
}
