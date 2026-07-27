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
  extractDutyMembers,
  calculateInactivePeriods,
  formatInstitutionsForGantt as formatInstitutionsForGanttHelper,
  buildInstitutionNamesMap,
  buildInstitutionTenantMap,
  buildInstitutionPublicMeetingsMap,
  buildInstitutionPeriodicityMap,
} from '../utils/ganttHelpers';

export function useGanttChartData(
  tenantInstitutionsRef: Ref<AtstovavimasInstitution[]>, // Lazy loaded tenant institutions
  availableTenants: AtstovavimasTenant[],
  tenantMeetingsRef: ComputedRef<GanttMeeting[]>, // Windowed meetings from useTenantMeetings
) {
  // Use the lazy-loaded tenant institutions directly
  const tenantInstitutions = tenantInstitutionsRef;

  // Meetings come from the windowed meetings store (loaded by visible date range)
  const tenantMeetings = tenantMeetingsRef;

  // Tenant gaps derived from all check-ins for each institution
  const tenantGaps = computed<AtstovavimasGap[]>(() => {
    const institutions = tenantInstitutions.value ?? [];

    return institutions.flatMap((institution) => {
      // Get all check-ins for this institution
      const checkIns = institution.check_ins ?? [];

      // Transform each check-in to a gap
      return checkIns.map((checkIn) => {
        if (!checkIn?.start_date || !checkIn?.end_date) return null;

        return {
          institution_id: institution.id,
          from: new Date(checkIn.start_date),
          until: new Date(checkIn.end_date),
          mode: 'no_meetings', // All check-ins represent "no meetings"
          note: checkIn.note || undefined,
        } as AtstovavimasGap;
      }).filter((gap): gap is AtstovavimasGap => gap !== null);
    });
  });

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

  // Institution name mappings for Gantt charts - use shared helpers
  const getInstitutionNames = (institutions: AtstovavimasInstitution[]) => {
    return buildInstitutionNamesMap(institutions);
  };

  const getTenantNames = () => {
    return cachedTenantNames.value;
  };

  const getInstitutionTenant = (institutions: AtstovavimasInstitution[]) => {
    return buildInstitutionTenantMap(institutions);
  };

  // Get public meetings lookup for institutions
  const getInstitutionHasPublicMeetings = (institutions: AtstovavimasInstitution[]) => {
    return buildInstitutionPublicMeetingsMap(institutions);
  };

  // Get meeting periodicity lookup for institutions (days between expected meetings)
  const getInstitutionPeriodicity = (institutions: AtstovavimasInstitution[]) => {
    return buildInstitutionPeriodicityMap(institutions);
  };

  // Format institutions for Gantt component - use shared helper
  const formatInstitutionsForGantt = (institutions: AtstovavimasInstitution[]): GanttInstitution[] => {
    return formatInstitutionsForGanttHelper(institutions);
  };

  // Format tenant institutions for Gantt
  const formattedTenantInstitutions = computed<GanttInstitution[]>(() => {
    return tenantInstitutions.value.map(i => ({
      id: i.id,
      name: String(i.name ?? ''),
      tenant_id: String(i.tenant_id ?? i.tenant?.id ?? ''),
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
    return extractDutyMembers(tenantInstitutions.value as unknown as AtstovavimasInstitution[]);
  });

  // Calculate inactive periods for tenant institutions - use shared helper
  const tenantInactivePeriods = computed<InactivePeriod[]>(() => {
    return calculateInactivePeriods(
      tenantInstitutions.value as unknown as AtstovavimasInstitution[],
      tenantDutyMembers.value,
    );
  });

  // Helper to extract duty members from user's institutions - use shared helper
  const getDutyMembersFromInstitutions = (institutions: AtstovavimasInstitution[]): GanttDutyMember[] => {
    return extractDutyMembers(institutions);
  };

  // Helper to calculate inactive periods from user's institutions - use shared helper
  const getInactivePeriodsFromInstitutions = (institutions: AtstovavimasInstitution[]): InactivePeriod[] => {
    const dutyMembers = extractDutyMembers(institutions);
    return calculateInactivePeriods(institutions, dutyMembers);
  };

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
    getInstitutionNames,
    getTenantNames,
    getInstitutionTenant,
    getInstitutionHasPublicMeetings,
    getInstitutionPeriodicity,
    formatInstitutionsForGantt,
    getDutyMembersFromInstitutions,
    getInactivePeriodsFromInstitutions,
  };
}
