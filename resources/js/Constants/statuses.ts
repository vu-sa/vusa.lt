import { DocumentStatus, GoalStatus, InstitutionActivityStatus, LearningImportance, LearningStage, LearningStepKind, MailingListMode, MailingListSyncStatus, SupportRequestStatus, VoteValue } from '@/Types/enums';

export type StatusRole = 'neutral' | 'info' | 'progress' | 'attention' | 'success' | 'danger';

export interface StatusPresentation {
  label: string;
  role: StatusRole;
  uppercase?: boolean;
}

/** Border, surface and ink per role — StatusBadge and any control that shows a chosen status. */
export const statusRoleClasses: Record<StatusRole, string> = {
  neutral: 'border-status-neutral-border bg-status-neutral-surface text-status-neutral',
  info: 'border-status-info-border bg-status-info-surface text-status-info',
  progress: 'border-status-progress-border bg-status-progress-surface text-status-progress',
  attention: 'border-status-attention-border bg-status-attention-surface text-status-attention',
  success: 'border-status-success-border bg-status-success-surface text-status-success',
  danger: 'border-status-danger-border bg-status-danger-surface text-status-danger',
};

export type ReservationResourceStatus = 'created' | 'reserved' | 'lent' | 'returned' | 'rejected' | 'cancelled';
export type MeetingCompletionStatus = 'complete' | 'incomplete' | 'no_items';
export type TaskStatus = 'completed' | 'cancelled' | 'open' | 'due_soon' | 'overdue';
export type ContentStatus = 'published' | 'scheduled' | 'draft';
export type ProblemStatus = 'open' | 'in_progress' | 'resolved';
export type MissingVoteStatus = 'not_recorded';
/** Derived by getAgendaItemStatus() from the item's type and outcome votes. */
export type AgendaItemStatus
  = | 'consensus'
    | 'student_aligned'
    | 'student_misaligned'
    | 'decision_positive'
    | 'decision_negative'
    | 'neutral_decided'
    | 'mixed'
    | 'no_vote'
    | 'deferred'
    | 'informational'
    | 'break'
    | 'withdrawn'
    | 'unset';
export type UnknownBenefitStatus = 'unknown';

export const reservationResourceStatuses: Record<ReservationResourceStatus, StatusPresentation> = {
  created: status('Pateikta', 'info'),
  reserved: status('Rezervuota', 'success'),
  lent: status('Paskolinta', 'progress'),
  returned: status('Grąžinta', 'neutral'),
  rejected: status('Atmesta', 'danger'),
  cancelled: status('Atšaukta', 'neutral'),
};

/** Mirrors MeetingCompletionService::calculate(). A complete meeting is the healthy default and shows no badge in a collection. */
export const meetingCompletionStatuses: Record<MeetingCompletionStatus, StatusPresentation> = {
  complete: status('Užpildyta', 'success'),
  incomplete: status('Neužpildyta', 'attention'),
  no_items: status('Nėra darbotvarkės', 'attention'),
};

/** The student perspective across a meeting; `neutral` (nothing to compare) gets no tag. */
export const meetingAlignmentStatuses = {
  all_match: status('Visi sutampa', 'success'),
  mixed: status('Iš dalies sutampa', 'attention'),
  all_mismatch: status('Visi nesutampa', 'danger'),
} satisfies Record<string, StatusPresentation>;

export const voteStatuses: Record<VoteValue | MissingVoteStatus, StatusPresentation> = {
  [VoteValue.Positive]: status('Už', 'success'),
  [VoteValue.Negative]: status('Prieš', 'danger'),
  [VoteValue.Neutral]: status('Susilaikė', 'neutral'),
  not_recorded: status('Nebalsuota', 'attention'),
};

export const studentBenefitStatuses: Record<VoteValue | UnknownBenefitStatus, StatusPresentation> = {
  [VoteValue.Positive]: status('Palanku', 'success'),
  [VoteValue.Negative]: status('Nepalanku', 'danger'),
  [VoteValue.Neutral]: status('Neutralu', 'neutral'),
  unknown: status('Nežinoma', 'attention'),
};

/** One word and role per agenda item outcome — admin records, lists and the public meeting page alike. */
export const agendaItemStatuses: Record<AgendaItemStatus, StatusPresentation> = {
  consensus: status('Pritarta bendru sutarimu', 'success'),
  student_aligned: status('Studentų pozicija priimta', 'success'),
  student_misaligned: status('Studentų pozicija nesutampa', 'danger'),
  decision_positive: status('Priimtas', 'success'),
  decision_negative: status('Nepriimtas', 'danger'),
  neutral_decided: status('Neutralus sprendimas', 'neutral'),
  mixed: status('Skirtingi rezultatai', 'info'),
  no_vote: status('Neaptartas', 'attention'),
  deferred: status('Atidėtas', 'neutral'),
  informational: status('Aptarimas', 'info'),
  break: status('Pertrauka', 'neutral'),
  withdrawn: status('Išbrauktas', 'neutral'),
  unset: status('Nepažymėtas', 'attention'),
};

/** The ink, surface, border and dot of one role, for places that colour a part rather than render a StatusBadge. */
export const statusRoleParts: Record<StatusRole, { text: string; surface: string; border: string; dot: string }> = {
  neutral: { text: 'text-status-neutral', surface: 'bg-status-neutral-surface', border: 'border-status-neutral-border', dot: 'bg-status-neutral' },
  info: { text: 'text-status-info', surface: 'bg-status-info-surface', border: 'border-status-info-border', dot: 'bg-status-info' },
  progress: { text: 'text-status-progress', surface: 'bg-status-progress-surface', border: 'border-status-progress-border', dot: 'bg-status-progress' },
  attention: { text: 'text-status-attention', surface: 'bg-status-attention-surface', border: 'border-status-attention-border', dot: 'bg-status-attention' },
  success: { text: 'text-status-success', surface: 'bg-status-success-surface', border: 'border-status-success-border', dot: 'bg-status-success' },
  danger: { text: 'text-status-danger', surface: 'bg-status-danger-surface', border: 'border-status-danger-border', dot: 'bg-status-danger' },
};

export const taskStatuses: Record<TaskStatus, StatusPresentation> = {
  completed: status('Atlikta', 'success'),
  cancelled: status('tasks.cancelled', 'neutral'),
  open: status('Atvira', 'info'),
  due_soon: status('Artėja terminas', 'attention'),
  overdue: status('Vėluoja', 'danger'),
};

export const contentStatuses: Record<ContentStatus, StatusPresentation> = {
  published: status('Paskelbta', 'success', true),
  scheduled: status('Suplanuota', 'success', true),
  draft: status('Juodraštis', 'neutral', true),
};

/** Whether a duty or institution still exists in the structure — not meeting health, which is `institutionActivityStatuses`. */
export const operatingStatuses = {
  active: status('Veikianti', 'success', true),
  inactive: status('Nebeveikianti', 'neutral', true),
} satisfies Record<string, StatusPresentation>;

export const inactiveInstitutionStatus: StatusPresentation = status('Nebeveikianti institucija', 'neutral');
export const notReservableStatus: StatusPresentation = status('Nerezervuojamas', 'neutral');
export const deletedMemberStatus: StatusPresentation = status('Ištrintas', 'neutral');

export const bannerStatuses = {
  active: status('Aktyvus', 'success', true),
  inactive: status('Neaktyvus', 'neutral', true),
};

/** A SharePoint archive file's publication on vusa.lt; `removed` overrides it once the file is gone. */
export const documentStatuses: Record<DocumentStatus | 'removed', StatusPresentation> = {
  [DocumentStatus.Pending]: status('Laukia', 'attention'),
  [DocumentStatus.Published]: status('Paskelbtas', 'success'),
  [DocumentStatus.Hidden]: status('Paslėptas', 'neutral'),
  removed: status('Pašalintas iš SharePoint', 'danger'),
};

export const problemStatuses: Record<ProblemStatus, StatusPresentation> = {
  open: status('Atvira', 'attention'),
  in_progress: status('Vykdoma', 'progress'),
  resolved: status('Išspręsta', 'success'),
};

/** Goals pilot. Uppercase tags like the publishing statuses: a goal's status is what a plan is read by. */
export const goalStatuses: Record<GoalStatus, StatusPresentation> = {
  [GoalStatus.Planned]: status('Planuojamas', 'info', true),
  [GoalStatus.InProgress]: status('Vykdomas', 'progress', true),
  [GoalStatus.Achieved]: status('Pasiektas', 'success', true),
  [GoalStatus.NotAchieved]: status('Nepasiektas', 'danger', true),
  [GoalStatus.Dropped]: status('Atsisakyta', 'neutral', true),
};

export const mailingListModes: Record<MailingListMode, StatusPresentation> = {
  [MailingListMode.Managed]: status('mailing_lists.modes.managed', 'success'),
  [MailingListMode.Unmanaged]: status('mailing_lists.modes.unmanaged', 'neutral'),
};

export const mailingListSyncStatuses: Record<MailingListSyncStatus, StatusPresentation> = {
  [MailingListSyncStatus.Synced]: status('mailing_lists.sync_statuses.synced', 'success'),
  [MailingListSyncStatus.NeedsReview]: status('mailing_lists.sync_statuses.needs_review', 'attention'),
  [MailingListSyncStatus.Failed]: status('mailing_lists.sync_statuses.failed', 'danger'),
};

export const supportRequestStatuses: Record<SupportRequestStatus, StatusPresentation> = {
  [SupportRequestStatus.New]: status('Naujas', 'info'),
  [SupportRequestStatus.Reviewing]: status('Peržiūrima', 'progress'),
  [SupportRequestStatus.Planned]: status('Suplanuota', 'info'),
  [SupportRequestStatus.InProgress]: status('Vykdoma', 'progress'),
  [SupportRequestStatus.Done]: status('Išspręsta', 'success'),
  [SupportRequestStatus.Declined]: status('Atmesta', 'danger'),
};

export const institutionActivityStatuses: Record<InstitutionActivityStatus, StatusPresentation> = {
  [InstitutionActivityStatus.NoActivity]: status('Nėra veiklos', 'neutral'),
  [InstitutionActivityStatus.Healthy]: status('Aktyvi', 'success'),
  [InstitutionActivityStatus.Approaching]: status('Artėja terminas', 'attention'),
  [InstitutionActivityStatus.Overdue]: status('Vėluoja', 'danger'),
  [InstitutionActivityStatus.CoveredByUpcomingMeeting]: status('Suplanuotas posėdis', 'info'),
  [InstitutionActivityStatus.CoveredByCheckIn]: status('Apie veiklą pranešta', 'info'),
  [InstitutionActivityStatus.RecentCheckIn]: status('activity_requests.recent_check_in', 'info'),
};

export type ActivityRequestStatus = 'pending' | 'answered' | 'resolved' | 'expired';

export const activityRequestStatuses: Record<ActivityRequestStatus, StatusPresentation> = {
  pending: status('activity_requests.pending', 'attention'),
  answered: status('activity_requests.answered', 'success'),
  resolved: status('activity_requests.resolved', 'success'),
  expired: status('activity_requests.expired', 'neutral'),
};

export type LearningCuratorFlag = 'expired_document' | 'updated_document' | 'missing_source';

export const learningStepKinds: Record<LearningStepKind, StatusPresentation> = {
  [LearningStepKind.Action]: status('learning.kinds.action', 'progress'),
  [LearningStepKind.MeetingPrep]: status('learning.kinds.meeting_prep', 'neutral'),
};

export const learningStepStages: Record<LearningStage, StatusPresentation> = {
  [LearningStage.BeforeStart]: status('learning.stages.before_start', 'info'),
  [LearningStage.FirstMonths]: status('learning.stages.first_months', 'progress'),
  [LearningStage.WholeTerm]: status('learning.stages.whole_term', 'neutral'),
  [LearningStage.TermEnd]: status('learning.stages.term_end', 'attention'),
};

export const learningStepImportances: Record<LearningImportance, StatusPresentation> = {
  [LearningImportance.Essential]: status('learning.importance.essential', 'attention'),
  [LearningImportance.Recommended]: status('learning.importance.recommended', 'info'),
  [LearningImportance.Optional]: status('learning.importance.optional', 'neutral'),
};

export const learningCuratorFlags: Record<LearningCuratorFlag, StatusPresentation> = {
  expired_document: status('learning_library.library.flags.expired_document', 'danger'),
  updated_document: status('learning_library.library.flags.updated_document', 'attention'),
  missing_source: status('learning_library.library.flags.missing_source', 'attention'),
};

function status(label: string, role: StatusRole, uppercase = false): StatusPresentation {
  return uppercase ? { label, role, uppercase } : { label, role };
}

/** A value from an untyped source (a search document, a facet) mapped to its status, or none when unnamed. */
export function statusFor<K extends string, T extends StatusPresentation>(map: Record<K, T>, value: string | null | undefined): T | null {
  return value && Object.hasOwn(map, value) ? map[value as K]! : null;
}

export const learningImportanceStatuses = {
  essential: status('learning.importance.essential', 'info'),
  recommended: status('learning.importance.recommended', 'neutral'),
  optional: status('learning.importance.optional', 'neutral'),
};

/** A discussion topic: only a closed one carries a badge. */
export const discussionTopicStatuses = {
  closed: status('discussions.topic.closed', 'success'),
};

export const learningProgressStatuses = {
  pending: status('learning.states.pending', 'neutral'),
  done: status('learning.states.done', 'success'),
  skipped: status('learning.states.skipped', 'neutral'),
  updated: status('learning.path.updated', 'info'),
};
