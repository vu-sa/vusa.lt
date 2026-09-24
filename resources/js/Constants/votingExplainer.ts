import type { AgendaItemStatus } from './statuses';

/**
 * What each agenda item status means, for the admin help and the public explainer alike.
 * Both render the word and role from `agendaItemStatuses`, so the two surfaces cannot drift apart
 * again; only the explanation is worded per audience (the admin one says what to do).
 */
export const agendaStatusExplanations: Array<{ status: AgendaItemStatus; admin: string; public: string }> = [
  { status: 'student_aligned', admin: 'voting.status_aligned_admin_help', public: 'Galutinis sprendimas atitinka studentų atstovų balsą' },
  { status: 'student_misaligned', admin: 'voting.status_misaligned_admin_help', public: 'Galutinis sprendimas skiriasi nuo studentų atstovų balso' },
  { status: 'neutral_decided', admin: 'voting.status_neutral_admin_help', public: 'Studentai susilaikė arba sprendimas neturėjo aiškios naudos/žalos' },
  { status: 'no_vote', admin: 'voting.status_no_vote_admin_help', public: 'Balsavimo klausimas, bet balsas neįvyko arba nepažymėtas' },
  { status: 'deferred', admin: 'voting.status_deferred_admin_help', public: 'Klausimo svarstymas atidėtas kitam posėdžiui' },
  { status: 'informational', admin: 'voting.status_informational_admin_help', public: 'Informacinio pobūdžio klausimas, balsavimo nereikalaujantis' },
  { status: 'unset', admin: 'voting.status_unset_admin_help', public: 'Klausimo tipas dar nebuvo nurodytas administratoriaus' },
];

/** The three recorded fields of a vote, in the order both explainers list them. */
export const voteFieldExplanations: Array<{ field: 'decision' | 'student_vote' | 'student_benefit'; label: string; admin: string; public: string }> = [
  { field: 'decision', label: 'Sprendimas', admin: 'voting.field_decision_tooltip', public: 'Galutinis viso organo sprendimas' },
  { field: 'student_vote', label: 'Studentų balsas', admin: 'voting.field_student_vote_tooltip', public: 'Kaip balsavo studentų atstovas ar atstovai' },
  { field: 'student_benefit', label: 'Nauda studentams', admin: 'voting.field_student_benefit_tooltip', public: 'VU SA įvertinimas, ar sprendimas naudingas studentams' },
];
