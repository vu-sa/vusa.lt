import { computed, type MaybeRefOrGetter, toValue } from 'vue';

import type {
  AtstovavimasUser,
  AtstovavimasInstitution,
  AtstovavimasMeeting,
  AtstovavimasVote,
  InstitutionInsights,
  GanttMeeting,
  GanttAgendaItem,
  AtstovavimasGap,
} from '../types';

import { getOutcomeVotes } from '@/Composables/useAgendaItemStyling';

export function useAtstovavimasData(
  userGetter: MaybeRefOrGetter<AtstovavimasUser>,
  userInstitutionsGetter: MaybeRefOrGetter<AtstovavimasInstitution[]> = [],
) {
  // User's direct institutions, including duties that begin later, then the ones they
  // look after as secretary (`is_secretary`), which no duty reaches.
  const institutions = computed<AtstovavimasInstitution[]>(() => {
    const user = toValue(userGetter);
    const secretaried = (toValue(userInstitutionsGetter) ?? []).filter(institution => institution.is_secretary ?? institution.is_administered);

    return [
      ...(user?.authorization_duties ?? []).map(duty => duty.institution ?? null),
      ...secretaried,
    ]
      .filter((institution): institution is AtstovavimasInstitution => institution !== null && institution !== undefined)
      .map((inst: AtstovavimasInstitution) => ({
        ...inst,
        hasUpcomingMeetings: Array.isArray(inst?.meetings)
          ? inst.meetings.some(meeting => new Date(meeting.start_time) > new Date())
          : false,
      }))
      // unique by id
      .filter((institution: AtstovavimasInstitution, index: number, self: AtstovavimasInstitution[]) =>
        index === self.findIndex((t: AtstovavimasInstitution) => t && institution && t.id === institution.id),
      );
  });

  // All meetings from user's institutions (internal computed for sortedMeetings)
  const meetings = computed<AtstovavimasMeeting[]>(() => {
    return institutions.value.flatMap((institution: AtstovavimasInstitution) => {
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
    return institutions.value.map((inst: AtstovavimasInstitution) => {
      return (inst.meetings ?? []).map((m) => {
        // Extract agenda items for tooltip (limit to first 4)
        const agendaItems = (m.agenda_items ?? []).slice(0, 4).map(item => ({
          id: String(item.id),
          title: String(item.title ?? ''),
          type: (item.type ?? null) as GanttAgendaItem['type'],
          votes: getOutcomeVotes(item) as AtstovavimasVote[],
        }));
        const totalAgendaCount = (m.agenda_items ?? []).length;

        return {
          id: m.id,
          start_time: new Date(m.start_time),
          institution_id: inst.id,
          institution: String(inst.name ?? ''),
          completion_status: m.completion_status,
          vote_alignment_status: m.vote_alignment_status,
          requires_student_perspective: m.requires_student_perspective,
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
  const userGaps = computed<AtstovavimasGap[]>(() => {
    return institutions.value.flatMap((inst: AtstovavimasInstitution) => {
      return (inst.check_ins ?? []).map(checkIn => ({
        institution_id: inst.id,
        from: new Date(checkIn.start_date),
        until: new Date(checkIn.end_date),
        mode: 'no_meetings', // All check-ins represent "no meetings"
        note: checkIn.note || undefined,
      }));
    });
  });

  // Sort all meetings from newest to oldest for the table
  const sortedMeetings = computed<AtstovavimasMeeting[]>(() => {
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
    sortedMeetings,
    institutionsInsights,
  };
}
