import type { LucideIcon } from '@lucide/vue';
import {
  Ban,
  CalendarCheck,
  CalendarClock,
  Circle,
  CircleCheck,
  CircleDashed,
  CircleDot,
  CircleHelp,
  CircleMinus,
  CircleSlash,
  CircleX,
  Clock3,
  Coffee,
  Eye,
  FilePenLine,
  Hand,
  Handshake,
  Inbox,
  ListX,
  LoaderCircle,
  MessagesSquare,
  PackageOpen,
  RefreshCw,
  RotateCcw,
  Search,
  Split,
  ThumbsDown,
  ThumbsUp,
  TriangleAlert,
  UserX,
} from '@lucide/vue';

import * as data from './statuses';

import { DocumentStatus, GoalStatus, InstitutionActivityStatus, MailingListMode, MailingListSyncStatus, SupportRequestStatus, VoteValue } from '@/Types/enums';

export * from './statuses';

export interface StatusPresentation extends data.StatusPresentation {
  icon: LucideIcon;
}

export const reservationResourceStatuses = {
  created: withIcon(data.reservationResourceStatuses.created, Inbox),
  reserved: withIcon(data.reservationResourceStatuses.reserved, CalendarCheck),
  lent: withIcon(data.reservationResourceStatuses.lent, PackageOpen),
  returned: withIcon(data.reservationResourceStatuses.returned, RotateCcw),
  rejected: withIcon(data.reservationResourceStatuses.rejected, CircleX),
  cancelled: withIcon(data.reservationResourceStatuses.cancelled, Ban),
};

export const meetingCompletionStatuses = {
  complete: withIcon(data.meetingCompletionStatuses.complete, CircleCheck),
  incomplete: withIcon(data.meetingCompletionStatuses.incomplete, CircleDashed),
  no_items: withIcon(data.meetingCompletionStatuses.no_items, CircleSlash),
};

export const meetingAlignmentStatuses = {
  all_match: withIcon(data.meetingAlignmentStatuses.all_match, CircleCheck),
  mixed: withIcon(data.meetingAlignmentStatuses.mixed, CircleDashed),
  all_mismatch: withIcon(data.meetingAlignmentStatuses.all_mismatch, TriangleAlert),
};

export const voteStatuses = {
  [VoteValue.Positive]: withIcon(data.voteStatuses[VoteValue.Positive], CircleCheck),
  [VoteValue.Negative]: withIcon(data.voteStatuses[VoteValue.Negative], CircleX),
  [VoteValue.Neutral]: withIcon(data.voteStatuses[VoteValue.Neutral], CircleMinus),
  not_recorded: withIcon(data.voteStatuses.not_recorded, CircleDashed),
};

export const studentBenefitStatuses = {
  [VoteValue.Positive]: withIcon(data.studentBenefitStatuses[VoteValue.Positive], ThumbsUp),
  [VoteValue.Negative]: withIcon(data.studentBenefitStatuses[VoteValue.Negative], ThumbsDown),
  [VoteValue.Neutral]: withIcon(data.studentBenefitStatuses[VoteValue.Neutral], CircleMinus),
  unknown: withIcon(data.studentBenefitStatuses.unknown, CircleDashed),
};

export const agendaItemStatuses = {
  consensus: withIcon(data.agendaItemStatuses.consensus, Handshake),
  student_aligned: withIcon(data.agendaItemStatuses.student_aligned, CircleCheck),
  student_misaligned: withIcon(data.agendaItemStatuses.student_misaligned, CircleX),
  decision_positive: withIcon(data.agendaItemStatuses.decision_positive, CircleCheck),
  decision_negative: withIcon(data.agendaItemStatuses.decision_negative, CircleX),
  neutral_decided: withIcon(data.agendaItemStatuses.neutral_decided, CircleMinus),
  mixed: withIcon(data.agendaItemStatuses.mixed, Split),
  no_vote: withIcon(data.agendaItemStatuses.no_vote, CircleDashed),
  deferred: withIcon(data.agendaItemStatuses.deferred, Clock3),
  informational: withIcon(data.agendaItemStatuses.informational, MessagesSquare),
  break: withIcon(data.agendaItemStatuses.break, Coffee),
  withdrawn: withIcon(data.agendaItemStatuses.withdrawn, ListX),
  unset: withIcon(data.agendaItemStatuses.unset, CircleHelp),
};

export const taskStatuses = {
  completed: withIcon(data.taskStatuses.completed, CircleCheck),
  cancelled: withIcon(data.taskStatuses.cancelled, Ban),
  open: withIcon(data.taskStatuses.open, Circle),
  due_soon: withIcon(data.taskStatuses.due_soon, Clock3),
  overdue: withIcon(data.taskStatuses.overdue, TriangleAlert),
};

export const contentStatuses = {
  published: withIcon(data.contentStatuses.published, Eye),
  scheduled: withIcon(data.contentStatuses.scheduled, CalendarClock),
  draft: withIcon(data.contentStatuses.draft, FilePenLine),
};

export const operatingStatuses = {
  active: withIcon(data.operatingStatuses.active, CircleCheck),
  inactive: withIcon(data.operatingStatuses.inactive, CircleSlash),
};

export const bannerStatuses = {
  active: withIcon(data.bannerStatuses.active, Eye),
  inactive: withIcon(data.bannerStatuses.inactive, CircleSlash),
};

export const documentStatuses = {
  [DocumentStatus.Pending]: withIcon(data.documentStatuses[DocumentStatus.Pending], Clock3),
  [DocumentStatus.Published]: withIcon(data.documentStatuses[DocumentStatus.Published], Eye),
  [DocumentStatus.Hidden]: withIcon(data.documentStatuses[DocumentStatus.Hidden], CircleSlash),
  removed: withIcon(data.documentStatuses.removed, CircleX),
};

export const problemStatuses = {
  open: withIcon(data.problemStatuses.open, CircleDot),
  in_progress: withIcon(data.problemStatuses.in_progress, LoaderCircle),
  resolved: withIcon(data.problemStatuses.resolved, CircleCheck),
};

export const goalStatuses = {
  [GoalStatus.Planned]: withIcon(data.goalStatuses[GoalStatus.Planned], CalendarClock),
  [GoalStatus.InProgress]: withIcon(data.goalStatuses[GoalStatus.InProgress], LoaderCircle),
  [GoalStatus.Achieved]: withIcon(data.goalStatuses[GoalStatus.Achieved], CircleCheck),
  [GoalStatus.NotAchieved]: withIcon(data.goalStatuses[GoalStatus.NotAchieved], CircleX),
  [GoalStatus.Dropped]: withIcon(data.goalStatuses[GoalStatus.Dropped], CircleSlash),
};

export const mailingListModes = {
  [MailingListMode.Managed]: withIcon(data.mailingListModes[MailingListMode.Managed], RefreshCw),
  [MailingListMode.Unmanaged]: withIcon(data.mailingListModes[MailingListMode.Unmanaged], Hand),
};

export const mailingListSyncStatuses = {
  [MailingListSyncStatus.Synced]: withIcon(data.mailingListSyncStatuses[MailingListSyncStatus.Synced], CircleCheck),
  [MailingListSyncStatus.NeedsReview]: withIcon(data.mailingListSyncStatuses[MailingListSyncStatus.NeedsReview], TriangleAlert),
  [MailingListSyncStatus.Failed]: withIcon(data.mailingListSyncStatuses[MailingListSyncStatus.Failed], CircleX),
};

export const supportRequestStatuses = {
  [SupportRequestStatus.New]: withIcon(data.supportRequestStatuses[SupportRequestStatus.New], Inbox),
  [SupportRequestStatus.Reviewing]: withIcon(data.supportRequestStatuses[SupportRequestStatus.Reviewing], Search),
  [SupportRequestStatus.Planned]: withIcon(data.supportRequestStatuses[SupportRequestStatus.Planned], CalendarClock),
  [SupportRequestStatus.InProgress]: withIcon(data.supportRequestStatuses[SupportRequestStatus.InProgress], LoaderCircle),
  [SupportRequestStatus.Done]: withIcon(data.supportRequestStatuses[SupportRequestStatus.Done], CircleCheck),
  [SupportRequestStatus.Declined]: withIcon(data.supportRequestStatuses[SupportRequestStatus.Declined], CircleX),
};

export const institutionActivityStatuses = {
  [InstitutionActivityStatus.NoActivity]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.NoActivity], CircleSlash),
  [InstitutionActivityStatus.Healthy]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.Healthy], CircleCheck),
  [InstitutionActivityStatus.Approaching]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.Approaching], Clock3),
  [InstitutionActivityStatus.Overdue]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.Overdue], TriangleAlert),
  [InstitutionActivityStatus.CoveredByUpcomingMeeting]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.CoveredByUpcomingMeeting], CalendarClock),
  [InstitutionActivityStatus.CoveredByCheckIn]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.CoveredByCheckIn], CalendarCheck),
  [InstitutionActivityStatus.RecentCheckIn]: withIcon(data.institutionActivityStatuses[InstitutionActivityStatus.RecentCheckIn], CalendarClock),
};

export const activityRequestStatuses = {
  pending: withIcon(data.activityRequestStatuses.pending, Clock3),
  answered: withIcon(data.activityRequestStatuses.answered, CircleCheck),
  resolved: withIcon(data.activityRequestStatuses.resolved, CalendarCheck),
  expired: withIcon(data.activityRequestStatuses.expired, CircleSlash),
};

export const inactiveInstitutionStatus = withIcon(data.inactiveInstitutionStatus, CircleSlash);

export const notReservableStatus = withIcon(data.notReservableStatus, Ban);

export const deletedMemberStatus = withIcon(data.deletedMemberStatus, UserX);

function withIcon(presentation: data.StatusPresentation, icon: LucideIcon): StatusPresentation {
  return { ...presentation, icon };
}
