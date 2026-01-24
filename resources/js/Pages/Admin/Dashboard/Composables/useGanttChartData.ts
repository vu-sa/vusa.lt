import { computed, type Ref } from 'vue';
import type { 
  AtstovavimosInstitution, 
  AtstovavimosGap, 
  GanttMeeting, 
  GanttInstitution,
  AtstovavimosTenant,
  GanttDutyMember,
  InactivePeriod 
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
  tenantInstitutionsRef: Ref<App.Entities.Institution[]>, // Lazy loaded tenant institutions
  availableTenants: AtstovavimosTenant[]
) {
  // Use the lazy-loaded tenant institutions directly
  const tenantInstitutions = tenantInstitutionsRef;

  // Get meetings from tenant institutions
  const tenantMeetings = computed<GanttMeeting[]>(() => {
    return tenantInstitutions.value.flatMap((institution: any) => {
      return (institution.meetings ?? []).map((meeting: any) => {
        // Extract agenda items for tooltip (limit to first 4)
        const agendaItems = (meeting.agenda_items ?? []).slice(0, 4).map((item: any) => {
          // Get main vote from: main_vote property, is_main flag in votes, or first vote
          const mainVote = item.main_vote 
            ?? item.votes?.find((v: any) => v.is_main) 
            ?? item.votes?.[0] 
            ?? null;
          return {
            id: String(item.id),
            title: String(item.title ?? ''),
            type: item.type ?? null,
            student_vote: mainVote?.student_vote ?? null,
            decision: mainVote?.decision ?? null,
          };
        });
        const totalAgendaCount = (meeting.agenda_items ?? []).length;

        return {
          id: String(meeting.id),
          start_time: new Date(meeting.start_time),
          institution_id: String(institution.id),
          institution: String(institution.name ?? ''),
          completion_status: meeting.completion_status,
          agenda_items: agendaItems,
          agenda_items_count: totalAgendaCount,
          has_report: meeting.has_report,
          has_protocol: meeting.has_protocol,
          // Extract meeting type for icon differentiation (in-person, remote, email)
          type_slug: meeting.type ?? meeting.type_slug,
        };
      });
    });
  });

  // Tenant gaps derived from all check-ins for each institution
  const tenantGaps = computed<AtstovavimosGap[]>(() => {
    const institutions = tenantInstitutions.value ?? [];

    return institutions.flatMap((inst: any) => {
      // Get all check-ins for this institution
      const checkIns = inst?.check_ins ?? [];

      // Transform each check-in to a gap
      return checkIns.map((ci: any) => {
        if (!ci?.start_date || !ci?.end_date) return null;

        return {
          institution_id: inst.id,
          from: new Date(ci.start_date),
          until: new Date(ci.end_date),
          mode: 'no_meetings',  // All check-ins represent "no meetings"
          note: ci.note || undefined
        } as AtstovavimosGap;
      }).filter(Boolean);
    }) as AtstovavimosGap[];
  });

  // All tenant meetings for processing
  const allTenantMeetings = computed(() => {
    return tenantInstitutions.value.map(institution => {
      return (institution as any).meetings?.map((meeting: any) => {
        return {
          institution: String(institution.name ?? ''),
          institution_id: institution.id,
          start_time: new Date(meeting.start_time),
          id: meeting.id,
        } as any;
      });
    })?.flat() ?? [];
  });

  // Memoized tenant names lookup - only computed once since availableTenants is static
  const cachedTenantNames = computed(() => {
    return Object.fromEntries((availableTenants ?? []).map(t => [t.id, t.shortname]));
  });

  // Institution name mappings for Gantt charts - use shared helpers
  const getInstitutionNames = (institutions: AtstovavimosInstitution[]) => {
    return buildInstitutionNamesMap(institutions);
  };

  const getTenantNames = () => {
    return cachedTenantNames.value;
  };

  const getInstitutionTenant = (institutions: AtstovavimosInstitution[]) => {
    return buildInstitutionTenantMap(institutions);
  };

  // Get public meetings lookup for institutions
  const getInstitutionHasPublicMeetings = (institutions: AtstovavimosInstitution[]) => {
    return buildInstitutionPublicMeetingsMap(institutions);
  };

  // Get meeting periodicity lookup for institutions (days between expected meetings)
  const getInstitutionPeriodicity = (institutions: AtstovavimosInstitution[]) => {
    return buildInstitutionPeriodicityMap(institutions);
  };

  // Format institutions for Gantt component - use shared helper
  const formatInstitutionsForGantt = (institutions: AtstovavimosInstitution[]): GanttInstitution[] => {
    return formatInstitutionsForGanttHelper(institutions);
  };

  // Format tenant institutions for Gantt
  const formattedTenantInstitutions = computed<GanttInstitution[]>(() => {
    return tenantInstitutions.value.map(i => ({
      id: i.id,
      name: String(i.name ?? ''),
      tenant_id: String(i.tenant_id ?? '')
    }));
  });

  // Calendar attributes for tenant meetings
  const tenantCalendarAttributes = computed<any[]>(() => {
    const attrs = allTenantMeetings.value?.map((meeting) => {
      const calendarAttrObject = {
        dates: [meeting?.start_time],
        dot: 'red',
        popover: {
          label: String(meeting?.institution ?? ''),
          isInteractive: true,
        },
        key: meeting?.id,
      };
      return calendarAttrObject;
    }) as any[] | undefined;

    attrs?.push({
      dates: [new Date()],
      highlight: { color: "red", fillMode: "outline" },
      order: 1,
    });

    return attrs ?? [];
  });

  // Check types of each duty, and duties.current_users amount
  type DutyTypeCount = { title: string; count: number; slug?: string | null | undefined };
  const dutyTypesWithUserCounts = computed<DutyTypeCount[] | undefined>(() => {
    return tenantInstitutions.value?.reduce((acc: DutyTypeCount[], institution: any) => {
      institution.duties?.forEach((duty: any) => {
        duty.types?.forEach((type: any) => {
          if (!type?.title) {
            return;
          }

          const existingType = acc.find((t) => t.title === type.title);

          if (existingType) {
            existingType.count += duty.current_users?.length ?? 0;
          } else {
            acc.push({
              title: type.title,
              count: duty.current_users?.length ?? 0,
              slug: type.slug,
            });
          }
        });
      });

      return acc;
    }, [] as DutyTypeCount[])?.sort((a, b) => b.count - a.count)
      .filter((type) => type.count > 0 && type.slug !== 'kuratoriai')
      .slice(0, 2);
  });

  // Extract duty members from tenant institutions for Gantt display - use shared helper
  const tenantDutyMembers = computed<GanttDutyMember[]>(() => {
    return extractDutyMembers(tenantInstitutions.value as unknown as AtstovavimosInstitution[]);
  });

  // Calculate inactive periods for tenant institutions - use shared helper
  const tenantInactivePeriods = computed<InactivePeriod[]>(() => {
    return calculateInactivePeriods(
      tenantInstitutions.value as unknown as AtstovavimosInstitution[],
      tenantDutyMembers.value
    );
  });

  // Helper to extract duty members from user's institutions - use shared helper
  const getDutyMembersFromInstitutions = (institutions: AtstovavimosInstitution[]): GanttDutyMember[] => {
    return extractDutyMembers(institutions);
  };

  // Helper to calculate inactive periods from institutions - use shared helper
  const getInactivePeriodsFromInstitutions = (institutions: AtstovavimosInstitution[]): InactivePeriod[] => {
    const dutyMembers = extractDutyMembers(institutions);
    return calculateInactivePeriods(institutions, dutyMembers);
  };

  return {
    // Tenant data
    tenantInstitutions,
    tenantMeetings,
    tenantGaps,
    allTenantMeetings,
    formattedTenantInstitutions,
    
    // Duty members data
    tenantDutyMembers,
    tenantInactivePeriods,
    
    // Calendar
    tenantCalendarAttributes,
    
    // Statistics
    dutyTypesWithUserCounts,
    
    // Helper functions
    getInstitutionNames,
    getTenantNames,
    getInstitutionTenant,
    getInstitutionHasPublicMeetings,
    getInstitutionPeriodicity,
    formatInstitutionsForGantt,
    getDutyMembersFromInstitutions,
    getInactivePeriodsFromInstitutions
  };
}
