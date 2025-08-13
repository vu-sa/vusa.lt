import { computed, type Ref } from 'vue';
import type { 
  AtstovavimosInstitution, 
  AtstovavimosGap, 
  GanttMeeting, 
  GanttInstitution,
  AtstovavimosTenant 
} from '../types';

export function useGanttChartData(
  accessibleInstitutions: App.Entities.Institution[],
  availableTenants: AtstovavimosTenant[],
  selectedTenantForGantt: Ref<string[]> // Changed to accept reactive reference
) {
  // Filter institutions by selected tenants
  const tenantInstitutions = computed(() => {
    if (!selectedTenantForGantt.value.length) {
      return accessibleInstitutions;
    }
    
    const filtered = accessibleInstitutions.filter(inst => {
      const tenantId = String(inst.tenant_id);
      const isIncluded = selectedTenantForGantt.value.includes(tenantId);
      return isIncluded;
    });
    
    return filtered;
  });

  // Get meetings from tenant institutions
  const tenantMeetings = computed<GanttMeeting[]>(() => {
    return tenantInstitutions.value.flatMap((institution: any) => {
      return (institution.meetings ?? []).map((meeting: any) => ({
        id: String(meeting.id),
        start_time: new Date(meeting.start_time),
        institution_id: String(institution.id),
        institution: String(institution.name ?? ''),
      }));
    });
  });

  // Tenant gaps derived from active_check_in per institution (future only)
  const tenantGaps = computed<AtstovavimosGap[]>(() => {
    const list: AtstovavimosGap[] = [];
    const institutions = tenantInstitutions.value ?? [];
    const from = new Date();
    from.setHours(0, 0, 0, 0);
    
    institutions.forEach((inst: any) => {
      const ci = inst?.active_check_in;
      if (!ci?.until_date) return;
      const until = new Date(ci.until_date);
      if (until.getTime() >= from.getTime()) {
        list.push({ 
          institution_id: inst.id, 
          from, 
          until, 
          mode: ci.mode === 'heads_up' ? 'heads_up' : 'no_meetings' 
        });
      }
    });
    return list;
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

  // Institution name mappings for Gantt charts
  const getInstitutionNames = (institutions: AtstovavimosInstitution[]) => {
    return Object.fromEntries(
      (institutions ?? []).map(i => [
        i.id, 
        String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id)
      ])
    );
  };

  const getTenantNames = () => {
    return Object.fromEntries((availableTenants ?? []).map(t => [t.id, t.shortname]));
  };

  const getInstitutionTenant = (institutions: AtstovavimosInstitution[]) => {
    return Object.fromEntries((institutions ?? []).map(i => [i.id, String(i.tenant?.id ?? '')]));
  };

  // Format institutions for Gantt component
  const formatInstitutionsForGantt = (institutions: AtstovavimosInstitution[]): GanttInstitution[] => {
    return institutions.map(i => ({
      id: i.id,
      name: String((i as any)?.name?.lt ?? (i as any)?.name?.en ?? (i as any)?.name ?? (i as any)?.shortname ?? i.id),
      tenant_id: i.tenant?.id
    }));
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

  return {
    // Tenant data
    tenantInstitutions,
    tenantMeetings,
    tenantGaps,
    allTenantMeetings,
    formattedTenantInstitutions,
    
    // Calendar
    tenantCalendarAttributes,
    
    // Statistics
    dutyTypesWithUserCounts,
    
    // Helper functions
    getInstitutionNames,
    getTenantNames,
    getInstitutionTenant,
    formatInstitutionsForGantt
  };
}
