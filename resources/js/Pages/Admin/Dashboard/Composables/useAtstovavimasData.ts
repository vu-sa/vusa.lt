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
      return (inst.meetings ?? []).map((m: any) => {
        // Extract agenda items for tooltip (limit to first 4)
        const agendaItems = (m.agenda_items ?? []).slice(0, 4).map((item: any) => ({
          id: String(item.id),
          title: String(item.title ?? ''),
          student_vote: item.student_vote ?? null,
          decision: item.decision ?? null,
        }));
        const totalAgendaCount = (m.agenda_items ?? []).length;

        return {
          id: m.id,
          start_time: new Date(m.start_time),
          institution_id: inst.id,
          institution: String(inst.name ?? ''),
          completion_status: m.completion_status,
          agenda_items: agendaItems,
          agenda_items_count: totalAgendaCount,
          has_report: m.has_report,
          has_protocol: m.has_protocol,
        };
      });
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
          mode: 'no_meetings',  // All check-ins represent "no meetings"
          note: ci.note || undefined
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
    
    // Find institutions with oldest last meetings and compare against periodicity
    // Only include institutions that are overdue (>100%) or approaching (>=80%) the periodicity threshold
    const APPROACHING_THRESHOLD = 0.8; // 80% of periodicity
    
    const institutionsWithOldMeetings = institutionsWithMeetings
      .map((inst: AtstovavimosInstitution) => {
        const lastMeeting = (inst.meetings as any[]).sort((a: any, b: any) => 
          new Date(b.start_time).getTime() - new Date(a.start_time).getTime()
        )[0];
        const daysSinceLastMeeting = Math.floor((new Date().getTime() - new Date(lastMeeting.start_time).getTime()) / (1000 * 60 * 60 * 24));
        // Get periodicity from institution (accessor handles inheritance from types, defaults to 30)
        const periodicity = (inst as any).meeting_periodicity_days ?? 30;
        const isOverdue = daysSinceLastMeeting > periodicity;
        const isApproaching = !isOverdue && daysSinceLastMeeting >= (periodicity * APPROACHING_THRESHOLD);
        return {
          ...inst,
          lastMeetingDate: new Date(lastMeeting.start_time),
          daysSinceLastMeeting,
          periodicity,
          isOverdue,
          isApproaching
        };
      })
      // Filter to only include institutions that need attention (overdue or approaching)
      .filter(inst => inst.isOverdue || inst.isApproaching)
      // Sort by how overdue they are (days over periodicity), then by days since last meeting
      .sort((a, b) => {
        const aOverdue = a.daysSinceLastMeeting - a.periodicity;
        const bOverdue = b.daysSinceLastMeeting - b.periodicity;
        if (aOverdue !== bOverdue) return bOverdue - aOverdue;
        return b.daysSinceLastMeeting - a.daysSinceLastMeeting;
      })
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
