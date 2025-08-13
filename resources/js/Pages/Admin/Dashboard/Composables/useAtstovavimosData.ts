import { computed } from 'vue';
import type { 
  AtstovavimosUser, 
  AtstovavimosInstitution, 
  AtstovavimosMeeting,
  InstitutionInsights,
  GanttMeeting,
  AtstovavimosGap
} from '../types';

export function useAtstovavimosData(
  user: AtstovavimosUser,
  accessibleInstitutions: App.Entities.Institution[]
) {
  // User's direct institutions (from current_duties)
  const institutions = computed<AtstovavimosInstitution[]>(() => {
    return (user?.current_duties ?? [])
      .map((duty: any) => duty?.institution ?? null)
      .filter((i: any): i is AtstovavimosInstitution => !!i)
      .map((inst: AtstovavimosInstitution) => ({
        ...inst,
        hasUpcomingMeetings: Array.isArray(inst?.meetings)
          ? inst.meetings.some((meeting: any) => new Date(meeting.start_time) > new Date())
          : false,
      }))
      // unique by id
      .filter((institution: AtstovavimosInstitution, index: number, self: AtstovavimosInstitution[]) =>
        index === self.findIndex((t: AtstovavimosInstitution) => t && institution && t.id === institution.id)
      );
  });

  // Additional institutions user can access (for admin functionality)
  const additionalInstitutions = computed(() => {
    const myIds = institutions.value.map(inst => inst.id);
    return accessibleInstitutions.filter(inst => !myIds.includes(inst.id));
  });

  // All meetings from user's institutions
  const meetings = computed<AtstovavimosMeeting[]>(() => {
    return institutions.value.flatMap((institution: AtstovavimosInstitution) => institution?.meetings ?? []);
  });

  // All user meetings flattened with institution mapping for Gantt
  const allUserMeetings = computed<GanttMeeting[]>(() => {
    return institutions.value.map((inst: AtstovavimosInstitution) => {
      return (inst.meetings ?? []).map((m: any) => ({
        id: m.id,
        start_time: new Date(m.start_time),
        institution_id: inst.id,
        institution: String(inst.name ?? '')
      }));
    }).flat();
  });

  // User gaps derived from active_check_in per institution (future only)
  const userGaps = computed<AtstovavimosGap[]>(() => {
    const from = new Date();
    from.setHours(0, 0, 0, 0);
    return institutions.value.map((inst: AtstovavimosInstitution) => {
      const ci = inst?.active_check_in;
      if (!ci?.until_date) return null;
      const until = new Date(ci.until_date);
      if (until.getTime() < from.getTime()) return null;
      return {
        institution_id: inst.id,
        from,
        until,
        mode: ci.mode === 'heads_up' ? 'heads_up' : 'no_meetings'
      } as const;
    }).filter(Boolean) as AtstovavimosGap[];
  });

  // Upcoming meetings sorted by date
  const upcomingMeetings = computed<AtstovavimosMeeting[]>(() => {
    return meetings.value
      .filter((meeting: any) => meeting && new Date(meeting.start_time) > new Date())
      .sort((a: any, b: any) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime());
  });

  // Sort all meetings from newest to oldest for the table
  const sortedMeetings = computed<AtstovavimosMeeting[]>(() => {
    return meetings.value.sort((a: any, b: any) => new Date(b.start_time).getTime() - new Date(a.start_time).getTime());
  });

  // Calculate institutions insights for footer information
  const institutionsInsights = computed<InstitutionInsights>(() => {
    const institutionsWithMeetings = institutions.value.filter((inst: AtstovavimosInstitution) => 
      Array.isArray(inst?.meetings) && inst.meetings.length > 0
    );
    const institutionsWithoutMeetings = institutions.value.filter((inst: AtstovavimosInstitution) => 
      !Array.isArray(inst?.meetings) || inst.meetings.length === 0
    );
    
    // Find institutions with oldest last meetings
    const institutionsWithOldMeetings = institutionsWithMeetings
      .map((inst: AtstovavimosInstitution) => {
        const lastMeeting = (inst.meetings as any[]).sort((a: any, b: any) => 
          new Date(b.start_time).getTime() - new Date(a.start_time).getTime()
        )[0];
        return {
          ...inst,
          lastMeetingDate: new Date(lastMeeting.start_time),
          daysSinceLastMeeting: Math.floor((new Date().getTime() - new Date(lastMeeting.start_time).getTime()) / (1000 * 60 * 60 * 24))
        };
      })
      .sort((a, b) => b.daysSinceLastMeeting - a.daysSinceLastMeeting)
      .slice(0, 2);
    
    return {
      withoutMeetings: institutionsWithoutMeetings,
      withOldMeetings: institutionsWithOldMeetings
    };
  });

  // Statistics
  const hasUpcomingMeetingCount = computed(() => 
    institutions.value.filter((institution: AtstovavimosInstitution) => !!institution?.hasUpcomingMeetings).length
  );

  return {
    institutions,
    additionalInstitutions,
    meetings,
    allUserMeetings,
    userGaps,
    upcomingMeetings,
    sortedMeetings,
    institutionsInsights,
    hasUpcomingMeetingCount
  };
}
