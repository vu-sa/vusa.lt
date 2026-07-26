import { computed, type MaybeRefOrGetter, toValue } from 'vue';

import type {
  AtstovavimosUser,
  AtstovavimosInstitution,
  AtstovavimosMeeting,
  InstitutionInsights,
  GanttMeeting,
  AtstovavimosGap,
} from '../types';

export function useAtstovavimosData(
  userGetter: MaybeRefOrGetter<AtstovavimosUser>,
) {
  // User's direct institutions (from current_duties)
  const institutions = computed<AtstovavimosInstitution[]>(() => {
    const user = toValue(userGetter);
    return (user?.current_duties ?? [])
      .map(duty => duty.institution ?? null)
      .filter((institution): institution is AtstovavimosInstitution => institution !== null)
      .map((inst: AtstovavimosInstitution) => ({
        ...inst,
        hasUpcomingMeetings: Array.isArray(inst?.meetings)
          ? inst.meetings.some(meeting => new Date(meeting.start_time) > new Date())
          : false,
      }))
      // unique by id
      .filter((institution: AtstovavimosInstitution, index: number, self: AtstovavimosInstitution[]) =>
        index === self.findIndex((t: AtstovavimosInstitution) => t && institution && t.id === institution.id),
      );
  });

  // All meetings from user's institutions (internal computed for upcomingMeetings and sortedMeetings)
  const meetings = computed<AtstovavimosMeeting[]>(() => {
    return institutions.value.flatMap((institution: AtstovavimosInstitution) => {
      return (institution?.meetings ?? []).map(meeting => ({
        ...meeting,
        institutions: [{
          id: institution.id,
          name: institution.name,
        }],
      }));
    });
  });

  // All user meetings flattened with institution mapping for Gantt
  const allUserMeetings = computed<GanttMeeting[]>(() => {
    return institutions.value.map((inst: AtstovavimosInstitution) => {
      return (inst.meetings ?? []).map((m) => {
        // Extract agenda items for tooltip (limit to first 4)
        // Vote data comes from: main_vote relationship, or vote with is_main flag, or first vote
        const agendaItems = (m.agenda_items ?? []).slice(0, 4).map((item) => {
          const mainVote = item.main_vote
            ?? item.votes?.find(vote => vote.is_main)
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
          // Extract meeting type for icon differentiation (in-person, remote, email)
          type_slug: m.type ?? m.type_slug,
        };
      });
    }).flat();
  });

  // User gaps derived from all check-ins for each institution
  const userGaps = computed<AtstovavimosGap[]>(() => {
    return institutions.value.flatMap((inst: AtstovavimosInstitution) => {
      return (inst.check_ins ?? []).map(checkIn => ({
        institution_id: inst.id,
        from: new Date(checkIn.start_date),
        until: new Date(checkIn.end_date),
        mode: 'no_meetings', // All check-ins represent "no meetings"
        note: checkIn.note || undefined,
      }));
    });
  });

  // Upcoming meetings (including all of today's meetings) sorted by date
  const upcomingMeetings = computed<AtstovavimosMeeting[]>(() => {
    const startOfToday = new Date();
    startOfToday.setHours(0, 0, 0, 0);

    return meetings.value
      .filter(meeting => new Date(meeting.start_time) >= startOfToday)
      .sort((a, b) => new Date(a.start_time).getTime() - new Date(b.start_time).getTime());
  });

  // Sort all meetings from newest to oldest for the table
  const sortedMeetings = computed<AtstovavimosMeeting[]>(() => {
    return [...meetings.value].sort((a, b) =>
      new Date(b.start_time).getTime() - new Date(a.start_time).getTime(),
    );
  });

  // Calculate institutions insights for footer information
  const institutionsInsights = computed<InstitutionInsights>(() => {
    return {
      attention: institutions.value
        .filter(institution => institution.activity_status.requires_action)
        .sort((a, b) => b.activity_status.priority - a.activity_status.priority)
        .slice(0, 2)
        .map(institution => ({
          id: institution.id,
          name: institution.name,
          ...institution.activity_status,
        })),
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
