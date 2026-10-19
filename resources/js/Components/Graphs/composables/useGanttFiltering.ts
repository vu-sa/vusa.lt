/**
 * Which institutions the meetings Gantt draws, in which order, and the data that belongs to them.
 */
import { computed, type ComputedRef } from 'vue';

import type { GanttSortBy } from '@/Pages/Admin/Dashboard/Composables/useGanttSettings';

export interface ParsedMeeting {
  id: string | number;
  start_time: string | Date;
  institution_id: string | number;
  title?: string;
  institution?: string;
  has_calendar_event?: boolean;
  calendar_event_is_draft?: boolean;
  completion_status?: 'complete' | 'incomplete' | 'no_items';
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
  showOnlyWithActivity: () => boolean;
  showOnlyWithPublicMeetings: () => boolean;
  /** Hide VU SA's own bodies, leaving only the ones it delegates into */
  hideInternalInstitutions?: () => boolean;
  institutionHasPublicMeetings: () => Record<string | number, boolean> | undefined;
  /**
   * "Has any past/planned activity", derived on the server so the filter does not depend on
   * which meeting windows happen to be loaded. Without it, the loaded meetings and gaps decide.
   */
  institutionHasActivity?: () => Record<string | number, boolean> | undefined;
  showDutyMembers: () => boolean;
  sortBy?: () => GanttSortBy;
}

interface FilterableInstitution {
  id: string | number;
  name?: string;
  is_internal?: boolean;
  last_meeting_date?: string | null;
  next_meeting_date?: string | null;
  last_activity_at?: string | null;
}

/** Date sorts: which field, and whether the soonest (next meeting) or the latest comes first. */
const DATE_SORTS: Record<Exclude<GanttSortBy, 'name'>, { field: 'last_activity_at' | 'next_meeting_date' | 'last_meeting_date'; ascending: boolean }> = {
  last_activity: { field: 'last_activity_at', ascending: false },
  next_meeting: { field: 'next_meeting_date', ascending: true },
  last_meeting: { field: 'last_meeting_date', ascending: false },
};

export interface GanttFilteringData {
  parsedMeetings: ComputedRef<ParsedMeeting[]>;
  parsedGaps: ComputedRef<ParsedGap[]>;
  parsedDutyMembers: ComputedRef<ParsedDutyMember[]>;
  parsedInactivePeriods: ComputedRef<ParsedInactivePeriod[]>;
  institutions: () => FilterableInstitution[] | undefined;
  institutionNames: () => Record<string | number, string> | undefined;
}

export function useGanttFiltering(options: GanttFilteringOptions, data: GanttFilteringData) {
  const activeInstitutionIds = computed(() => {
    const ids = new Set<string>();
    data.parsedMeetings.value.forEach(m => ids.add(String(m.institution_id)));
    data.parsedGaps.value.forEach(g => ids.add(String(g.institution_id)));
    return ids;
  });

  const getInstitutionName = (id: string | number): string => {
    const fromNames = data.institutionNames()?.[id];
    if (fromNames) return String(fromNames);
    const fromList = data.institutions()?.find(i => String(i.id) === String(id))?.name;
    return String(fromList ?? id);
  };

  const byId = computed(() => new Map((data.institutions() ?? []).map(i => [String(i.id), i])));

  const compareInstitutions = (a: string | number, b: string | number): number => {
    const byName = getInstitutionName(a).localeCompare(getInstitutionName(b));
    const sortBy = options.sortBy?.() ?? 'name';
    if (sortBy === 'name') return byName;

    const { field, ascending } = DATE_SORTS[sortBy];
    const timeOf = (id: string | number) => {
      const value = byId.value.get(String(id))?.[field];
      return value ? new Date(value).getTime() : null;
    };
    const [timeA, timeB] = [timeOf(a), timeOf(b)];

    // Rows without the date go last, whichever the direction.
    if (timeA === null || timeB === null) {
      return timeA === timeB ? byName : (timeA === null ? 1 : -1);
    }

    return (ascending ? timeA - timeB : timeB - timeA) || byName;
  };

  /** Institution ids after filters, in the chosen sort order */
  const institutions = computed(() => {
    let ids = (data.institutions() ?? []).map(i => i.id);

    if (options.showOnlyWithActivity()) {
      const hasActivity = options.institutionHasActivity?.();
      ids = ids.filter(id => (hasActivity ? Boolean(hasActivity[id] ?? hasActivity[String(id)]) : activeInstitutionIds.value.has(String(id))));
    }

    const publicMeetings = options.institutionHasPublicMeetings();
    if (options.showOnlyWithPublicMeetings() && publicMeetings) {
      ids = ids.filter(id => publicMeetings[id] || publicMeetings[String(id)]);
    }

    if (options.hideInternalInstitutions?.()) {
      const internal = new Set((data.institutions() ?? []).filter(i => i.is_internal).map(i => String(i.id)));
      ids = ids.filter(id => !internal.has(String(id)));
    }

    return ids.sort(compareInstitutions);
  });

  const visibleIds = computed(() => new Set(institutions.value.map(String)));
  const onVisibleInstitution = <T extends { institution_id: string | number }>(items: T[]) =>
    items.filter(item => visibleIds.value.has(String(item.institution_id)));

  const filteredMeetings = computed(() => onVisibleInstitution(data.parsedMeetings.value));
  const filteredGaps = computed(() => onVisibleInstitution(data.parsedGaps.value));
  const filteredDutyMembers = computed(() => (options.showDutyMembers() ? onVisibleInstitution(data.parsedDutyMembers.value) : []));
  const filteredInactivePeriods = computed(() => (options.showDutyMembers() ? onVisibleInstitution(data.parsedInactivePeriods.value) : []));

  return {
    institutions,
    filteredMeetings,
    filteredGaps,
    filteredDutyMembers,
    filteredInactivePeriods,
  };
}
