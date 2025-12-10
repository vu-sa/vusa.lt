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
      return (institution.meetings ?? []).map((meeting: any) => {
        // Extract agenda items for tooltip (limit to first 4)
        const agendaItems = (meeting.agenda_items ?? []).slice(0, 4).map((item: any) => ({
          id: String(item.id),
          title: String(item.title ?? ''),
          student_vote: item.student_vote ?? null,
          decision: item.decision ?? null,
        }));
        const totalAgendaCount = (meeting.agenda_items ?? []).length;

        return {
          id: String(meeting.id),
          start_time: new Date(meeting.start_time),
          institution_id: String(institution.id),
          institution: String(institution.name ?? ''),
          completion_status: meeting.completion_status,
          agenda_items: agendaItems,
          agenda_items_count: totalAgendaCount,
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
    return cachedTenantNames.value;
  };

  const getInstitutionTenant = (institutions: AtstovavimosInstitution[]) => {
    return Object.fromEntries((institutions ?? []).map(i => [i.id, String(i.tenant?.id ?? '')]));
  };

  // Get public meetings lookup for institutions
  const getInstitutionHasPublicMeetings = (institutions: AtstovavimosInstitution[]) => {
    return Object.fromEntries((institutions ?? []).map(i => [String(i.id), Boolean(i.has_public_meetings)]));
  };

  // Get meeting periodicity lookup for institutions (days between expected meetings)
  const getInstitutionPeriodicity = (institutions: AtstovavimosInstitution[]) => {
    return Object.fromEntries((institutions ?? []).map(i => [
      String(i.id), 
      (i as any).meeting_periodicity_days ?? 30
    ]));
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

  // Extract duty members from tenant institutions for Gantt display
  const tenantDutyMembers = computed<GanttDutyMember[]>(() => {
    const members: GanttDutyMember[] = [];
    
    for (const institution of tenantInstitutions.value) {
      const inst = institution as any;
      for (const duty of (inst.duties ?? [])) {
        for (const user of (duty.users ?? [])) {
          // Access pivot data for start_date and end_date
          const pivot = user.pivot ?? {};
          if (!pivot.start_date) continue;
          
          members.push({
            institution_id: String(institution.id),
            duty_id: String(duty.id),
            user: {
              id: String(user.id),
              name: String(user.name ?? ''),
              profile_photo_path: user.profile_photo_path ?? null
            },
            start_date: new Date(pivot.start_date),
            end_date: pivot.end_date ? new Date(pivot.end_date) : null
          });
        }
      }
    }
    
    return members;
  });

  // Calculate inactive periods for tenant institutions (when no duty members were active)
  const tenantInactivePeriods = computed<InactivePeriod[]>(() => {
    const periods: InactivePeriod[] = [];
    
    for (const institution of tenantInstitutions.value) {
      const instId = String(institution.id);
      const instMembers = tenantDutyMembers.value.filter(m => m.institution_id === instId);
      
      if (instMembers.length === 0) continue;
      
      // Sort members by start_date
      const sortedMembers = [...instMembers].sort((a, b) => 
        a.start_date.getTime() - b.start_date.getTime()
      );
      
      // Find gaps where no member was active
      // Build a timeline of active periods
      const activePeriods: Array<{ from: Date; until: Date }> = [];
      
      for (const member of sortedMembers) {
        const from = member.start_date;
        const until = member.end_date ?? new Date(); // If no end_date, assume still active
        
        // Merge overlapping periods
        if (activePeriods.length === 0) {
          activePeriods.push({ from, until });
        } else {
          const last = activePeriods[activePeriods.length - 1]!;
          if (from <= last.until) {
            // Overlapping - extend the last period if needed
            if (until > last.until) {
              last.until = until;
            }
          } else {
            // Gap found - this is an inactive period
            periods.push({
              institution_id: instId,
              from: last.until,
              until: from
            });
            activePeriods.push({ from, until });
          }
        }
      }
    }
    
    return periods;
  });

  // Helper to extract duty members from user's institutions (for user tab)
  const getDutyMembersFromInstitutions = (institutions: AtstovavimosInstitution[]): GanttDutyMember[] => {
    const members: GanttDutyMember[] = [];
    
    for (const institution of institutions) {
      const inst = institution as any;
      for (const duty of (inst.duties ?? [])) {
        for (const user of (duty.users ?? [])) {
          const pivot = user.pivot ?? {};
          if (!pivot.start_date) continue;
          
          members.push({
            institution_id: String(institution.id),
            duty_id: String(duty.id),
            user: {
              id: String(user.id),
              name: String(user.name ?? ''),
              profile_photo_path: user.profile_photo_path ?? null
            },
            start_date: new Date(pivot.start_date),
            end_date: pivot.end_date ? new Date(pivot.end_date) : null
          });
        }
      }
    }
    
    return members;
  };

  // Helper to calculate inactive periods from institutions
  const getInactivePeriodsFromInstitutions = (institutions: AtstovavimosInstitution[]): InactivePeriod[] => {
    const dutyMembers = getDutyMembersFromInstitutions(institutions);
    const periods: InactivePeriod[] = [];
    
    for (const institution of institutions) {
      const instId = String(institution.id);
      const instMembers = dutyMembers.filter(m => m.institution_id === instId);
      
      if (instMembers.length === 0) continue;
      
      const sortedMembers = [...instMembers].sort((a, b) => 
        a.start_date.getTime() - b.start_date.getTime()
      );
      
      const activePeriods: Array<{ from: Date; until: Date }> = [];
      
      for (const member of sortedMembers) {
        const from = member.start_date;
        const until = member.end_date ?? new Date();
        
        if (activePeriods.length === 0) {
          activePeriods.push({ from, until });
        } else {
          const last = activePeriods[activePeriods.length - 1]!;
          if (from <= last.until) {
            if (until > last.until) {
              last.until = until;
            }
          } else {
            periods.push({
              institution_id: instId,
              from: last.until,
              until: from
            });
            activePeriods.push({ from, until });
          }
        }
      }
    }
    
    return periods;
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
