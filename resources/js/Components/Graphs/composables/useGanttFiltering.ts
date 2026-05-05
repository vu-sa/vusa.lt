/**
 * useGanttFiltering - Gantt chart data filtering and sorting
 *
 * Extracted from MeetingsGantt.vue to reduce component complexity.
 * Handles:
 * - Institution filtering by tenant, activity, public meetings
 * - Institution sorting by order or name
 * - Filtering meetings, gaps, duty members, inactive periods by visible institutions
 * - Grouping duty members for avatar stacking
 */
import { computed, type ComputedRef } from 'vue';

export interface ParsedMeeting {
  id: string | number;
  start_time: string | Date;
  institution_id: string | number;
  title?: string;
  institution?: string;
  date: Date;
}

export interface ParsedGap {
  institution_id: string | number;
  from: string | Date;
  until: string | Date;
  mode?: 'heads_up' | 'no_meetings';
  note?: string;
  fromDate: Date;
  untilDate: Date;
}

export interface ParsedDutyMember {
  institution_id: string | number;
  user: { id: string; name: string; profile_photo_path?: string | null };
  start_date: string | Date;
  end_date?: string | Date | null;
  startDate: Date;
  endDate: Date | null;
}

export interface ParsedInactivePeriod {
  institution_id: string | number;
  from: string | Date;
  until: string | Date;
  fromDate: Date;
  untilDate: Date;
}

export interface GanttFilteringOptions {
  /** Tenant IDs to filter by (getter for reactivity) */
  tenantFilter: () => Array<string | number> | undefined;
  /** Institution to tenant mapping (getter for reactivity) */
  institutionTenant: () => Record<string | number, string | number> | undefined;
  /** Whether to show only institutions with activity */
  showOnlyWithActivity: () => boolean;
  /** Whether to show only institutions with public meetings */
  showOnlyWithPublicMeetings: () => boolean;
  /** Map of institution ID to public meetings flag (getter for reactivity) */
  institutionHasPublicMeetings: () => Record<string | number, boolean> | undefined;
  /** Custom institution order (getter for reactivity) */
  institutionsOrder: () => Array<string | number> | undefined;
  /** Whether to show duty members */
  showDutyMembers: () => boolean;
}

export interface GanttFilteringData {
  /** Raw meetings from props (parsed with Date objects) */
  parsedMeetings: ComputedRef<ParsedMeeting[]>;
  /** Raw gaps from props (parsed with Date objects) */
  parsedGaps: ComputedRef<ParsedGap[]>;
  /** Raw duty members from props (parsed with Date objects) */
  parsedDutyMembers: ComputedRef<ParsedDutyMember[]>;
  /** Raw inactive periods from props (parsed with Date objects) */
  parsedInactivePeriods: ComputedRef<ParsedInactivePeriod[]>;
  /** Institutions from props (getter for reactivity) */
  institutions: () => Array<{ id: string | number; name?: string }> | undefined;
  /** Institution names lookup (getter for reactivity) */
  institutionNames: () => Record<string | number, string> | undefined;
}

export function useGanttFiltering(
  options: GanttFilteringOptions,
  data: GanttFilteringData,
) {
  /**
   * Active institution IDs (those with meetings or gaps)
   */
  const activeInstitutionIds = computed(() => {
    const s = new Set<string | number>();
    data.parsedMeetings.value.forEach(m => s.add(m.institution_id));
    data.parsedGaps.value.forEach(g => s.add(g.institution_id));
    return s;
  });

  /**
   * Base institution IDs: explicit institutions prop + those referenced by data
   */
  const baseInstitutionIds = computed(() => {
    const ids = new Set<string | number>();
    const inst = data.institutions();
    if (inst) inst.forEach(i => ids.add(i.id));
    activeInstitutionIds.value.forEach(i => ids.add(i));
    return Array.from(ids);
  });

  /**
   * Get institution name for sorting
   */
  const getInstitutionName = (id: string | number): string => {
    const names = data.institutionNames();
    const fromProp = names?.[id as keyof typeof names];
    if (fromProp) return String(fromProp);
    const inst = data.institutions();
    const fromList = inst?.find(i => i.id === id)?.name;
    if (fromList) return String(fromList);
    const m = data.parsedMeetings.value.find(mm => mm.institution_id === id);
    return String(m?.institution ?? id);
  };

  /**
   * Final list of institution IDs after filters and sorting
   */
  const institutions = computed(() => {
    const ids = new Set<string | number>();
    baseInstitutionIds.value.forEach(id => ids.add(id));
    let arr = Array.from(ids);

    const tenantFilter = options.tenantFilter();
    const institutionTenant = options.institutionTenant();
    const showOnlyWithActivity = options.showOnlyWithActivity();
    const showOnlyWithPublicMeetings = options.showOnlyWithPublicMeetings();
    const institutionHasPublicMeetings = options.institutionHasPublicMeetings();
    const institutionsOrder = options.institutionsOrder();

    // Filter by tenant if requested
    if (tenantFilter && tenantFilter.length && institutionTenant) {
      const filterSet = new Set(tenantFilter.map(v => String(v)));
      arr = arr.filter(id =>
        filterSet.has(String(institutionTenant[id as keyof typeof institutionTenant])),
      );
    }

    // Filter only those with activity if requested
    if (showOnlyWithActivity) {
      const act = activeInstitutionIds.value;
      arr = arr.filter(id => act.has(id));
    }

    // Filter only those with public meetings if requested
    if (showOnlyWithPublicMeetings && institutionHasPublicMeetings) {
      const pubMap = institutionHasPublicMeetings;
      arr = arr.filter(id => pubMap[id] || pubMap[String(id)]);
    }

    // Sort by custom order or alphabetically by name
    if (institutionsOrder?.length) {
      const orderMap = new Map(institutionsOrder.map((id, idx) => [String(id), idx]));
      arr = arr.sort((a, b) => (orderMap.get(String(a)) ?? 1e9) - (orderMap.get(String(b)) ?? 1e9));
    }
    else {
      arr = arr.sort((a, b) => getInstitutionName(a).localeCompare(getInstitutionName(b)));
    }

    return arr;
  });

  /**
   * Meetings filtered to visible institutions
   */
  const filteredMeetings = computed(() => {
    const visibleIds = new Set(institutions.value);
    return data.parsedMeetings.value.filter(m => visibleIds.has(m.institution_id));
  });

  /**
   * Gaps filtered to visible institutions
   */
  const filteredGaps = computed(() => {
    const visibleIds = new Set(institutions.value);
    return data.parsedGaps.value.filter(g => visibleIds.has(g.institution_id));
  });

  /**
   * Duty members filtered to visible institutions
   */
  const filteredDutyMembers = computed(() => {
    if (!options.showDutyMembers()) return [];
    const visibleIds = new Set(institutions.value.map(String));
    return data.parsedDutyMembers.value.filter(m => visibleIds.has(String(m.institution_id)));
  });

  /**
   * Inactive periods filtered to visible institutions
   */
  const filteredInactivePeriods = computed(() => {
    if (!options.showDutyMembers()) return [];
    const visibleIds = new Set(institutions.value.map(String));
    return data.parsedInactivePeriods.value.filter(p => visibleIds.has(String(p.institution_id)));
  });

  /**
   * Duty members grouped by institution + day for avatar stacking
   */
  const groupedDutyMembers = computed(() => {
    const groups = new Map<string, typeof filteredDutyMembers.value>();
    for (const member of filteredDutyMembers.value) {
      const dayKey = `${member.institution_id}:${member.startDate.toDateString()}`;
      const arr = groups.get(dayKey) ?? [];
      arr.push(member);
      groups.set(dayKey, arr);
    }
    return groups;
  });

  return {
    activeInstitutionIds,
    baseInstitutionIds,
    institutions,
    filteredMeetings,
    filteredGaps,
    filteredDutyMembers,
    filteredInactivePeriods,
    groupedDutyMembers,
  };
}
