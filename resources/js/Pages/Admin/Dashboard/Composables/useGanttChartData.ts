import { computed, type ComputedRef, type Ref } from 'vue';

import type {
  AtstovavimasInstitution,
  AtstovavimasGap,
  GanttMeeting,
  GanttInstitution,
  AtstovavimasTenant,
  GanttDutyMember,
  InactivePeriod,
} from '../types';
import {
  extractCheckInGaps,
  extractDutyMembers,
  calculateInactivePeriods,
  buildInstitutionPeriodicityMap,
  ganttInstitutionTypes,
  ganttActivityDates,
} from '../utils/ganttHelpers';

export function useGanttChartData(
  tenantInstitutionsRef: Ref<AtstovavimasInstitution[]>, // Lazy loaded tenant institutions
  availableTenants: AtstovavimasTenant[],
  tenantMeetingsRef: ComputedRef<GanttMeeting[]>, // Windowed meetings from useTenantMeetings
  /** Rows whose member history has arrived; kept apart so a batch does not recompute every row. */
  memberRowsRef?: Ref<AtstovavimasInstitution[]>,
) {
  // Use the lazy-loaded tenant institutions directly
  const tenantInstitutions = tenantInstitutionsRef;

  // Meetings come from the windowed meetings store (loaded by visible date range)
  const tenantMeetings = tenantMeetingsRef;

  const tenantGaps = computed<AtstovavimasGap[]>(() => extractCheckInGaps(tenantInstitutions.value ?? []));

  // Institutions keyed by id for quick lookup (tenant names etc.)
  const cachedTenantNames = computed(() => {
    const names = Object.fromEntries((availableTenants ?? []).map(t => [String(t.id), t.shortname]));

    tenantInstitutions.value.forEach((institution) => {
      if (institution.tenant?.id && institution.tenant.shortname) {
        names[String(institution.tenant.id)] = institution.tenant.shortname;
      }
    });

    return names;
  });

  const getTenantNames = () => {
    return cachedTenantNames.value;
  };

  // Get meeting periodicity lookup for institutions (days between expected meetings)
  const getInstitutionPeriodicity = (institutions: AtstovavimasInstitution[]) => {
    return buildInstitutionPeriodicityMap(institutions);
  };

  // Format tenant institutions for Gantt
  const formattedTenantInstitutions = computed<GanttInstitution[]>(() => {
    return tenantInstitutions.value.map(i => ({
      id: i.id,
      name: String(i.name ?? ''),
      tenant_id: String(i.tenant_id ?? i.tenant?.id ?? ''),
      types: ganttInstitutionTypes(i),
      is_internal: i.is_internal,
      authorized: i.authorized,
      ...ganttActivityDates(i),
    }));
  });

  /**
   * Institutions that have ever had (or have planned) activity: used by the
   * "show only with activity" filter. Derived from server-provided aggregates
   * rather than the loaded meetings, so the filter is independent of which
   * meeting windows happen to be fetched.
   */
  const tenantInstitutionHasActivity = computed<Record<string, boolean>>(() => {
    const result: Record<string, boolean> = {};
    for (const institution of tenantInstitutions.value) {
      result[String(institution.id)] = Boolean(
        institution.last_meeting_date
        || (institution.upcoming_meetings_count ?? 0) > 0
        || institution.active_check_in,
      );
    }
    return result;
  });

  // Extract duty members from tenant institutions for Gantt display - use shared helper
  const tenantDutyMembers = computed<GanttDutyMember[]>(() => {
    return extractDutyMembers((memberRowsRef ?? tenantInstitutions).value as unknown as AtstovavimasInstitution[]);
  });

  // Calculate inactive periods for tenant institutions - use shared helper
  const tenantInactivePeriods = computed<InactivePeriod[]>(() => {
    return calculateInactivePeriods(
      (memberRowsRef ?? tenantInstitutions).value as unknown as AtstovavimasInstitution[],
      tenantDutyMembers.value,
    );
  });

  return {
    // Tenant data
    tenantInstitutions,
    tenantMeetings,
    tenantGaps,
    formattedTenantInstitutions,
    tenantInstitutionHasActivity,

    // Duty members data
    tenantDutyMembers,
    tenantInactivePeriods,

    // Helper functions
    getTenantNames,
    getInstitutionPeriodicity,
  };
}
