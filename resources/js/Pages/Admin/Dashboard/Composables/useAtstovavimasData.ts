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
  user: AtstovavimosUser
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

  // All meetings from user's institutions (internal computed for upcomingMeetings and sortedMeetings)
  const meetings = computed<AtstovavimosMeeting[]>(() => {
    return institutions.value.flatMap((institution: AtstovavimosInstitution) => {
      return (institution?.meetings ?? []).map(meeting => ({
        ...meeting,
        institutions: [{
          id: institution.id,
          name: institution.name
        }]
      }));
    });
  });

  // All user meetings flattened with institution mapping for Gantt
  const allUserMeetings = computed<GanttMeeting[]>(() => {
    return institutions.value.map((inst: AtstovavimosInstitution) => {
      return (inst.meetings ?? []).map((m: any) => ({
        id: m.id,
        start_time: new Date(m.start_time),
        institution_id: inst.id,
        institution: String(inst.name ?? ''),
        completion_status: m.completion_status
      }));
    }).flat();
  });

  // User gaps derived from all check-ins for each institution
  const userGaps = computed<AtstovavimosGap[]>(() => {
    return institutions.value.flatMap((inst: AtstovavimosInstitution) => {
      // Get all check-ins for this institution
      const checkIns = (inst as any).check_ins ?? [];

      // Transform each check-in to a gap
      return checkIns.map((ci: any) => {
        if (!ci?.start_date || !ci?.end_date) return null;

        return {
          institution_id: inst.id,
          from: new Date(ci.start_date),
          until: new Date(ci.end_date),
          mode: 'no_meetings'  // All check-ins represent "no meetings"
        } as AtstovavimosGap;
      }).filter(Boolean);
    }) as AtstovavimosGap[];
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

  return {
    institutions,
    allUserMeetings,
    userGaps,
    upcomingMeetings,
    sortedMeetings,
    institutionsInsights,
  };
}
