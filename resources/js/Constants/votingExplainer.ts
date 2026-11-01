import type { AgendaItemStatus } from './statuses';

/**
 * What each agenda item status means, for the admin help. The word and role come from
 * `agendaItemStatuses`; the explanation says what to do.
 */
export const agendaStatusExplanations: Array<{ status: AgendaItemStatus; admin: string }> = [
  { status: 'student_aligned', admin: 'voting.status_aligned_admin_help' },
  { status: 'student_misaligned', admin: 'voting.status_misaligned_admin_help' },
  { status: 'neutral_decided', admin: 'voting.status_neutral_admin_help' },
  { status: 'mixed', admin: 'voting.status_mixed_admin_help' },
  { status: 'no_vote', admin: 'voting.status_no_vote_admin_help' },
  { status: 'deferred', admin: 'voting.status_deferred_admin_help' },
  { status: 'informational', admin: 'voting.status_informational_admin_help' },
  { status: 'withdrawn', admin: 'voting.status_withdrawn_admin_help' },
  { status: 'unset', admin: 'voting.status_unset_admin_help' },
];

/** The three recorded fields of a vote, in the order both explainers list them. */
export const voteFieldExplanations: Array<{ field: 'decision' | 'student_vote' | 'student_benefit'; label: string; admin: string; public: string }> = [
  { field: 'decision', label: 'Sprendimas', admin: 'voting.field_decision_tooltip', public: 'voting.explainer.field_decision' },
  { field: 'student_vote', label: 'Studentų balsas', admin: 'voting.field_student_vote_tooltip', public: 'voting.explainer.field_student_vote' },
  { field: 'student_benefit', label: 'Nauda studentams', admin: 'voting.field_student_benefit_tooltip', public: 'voting.explainer.field_student_benefit' },
];
