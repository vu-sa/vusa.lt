import type { LucideIcon } from 'lucide-vue-next';
import {
  Ban,
  CalendarCheck,
  CalendarClock,
  Circle,
  CircleCheck,
  CircleDashed,
  CircleDot,
  CircleMinus,
  CircleSlash,
  CircleX,
  Clock3,
  Eye,
  FilePenLine,
  Inbox,
  LoaderCircle,
  PackageOpen,
  RotateCcw,
  Search,
  ThumbsDown,
  ThumbsUp,
  TriangleAlert,
} from 'lucide-vue-next';

import { InstitutionActivityStatus, SupportRequestStatus, VoteValue } from '@/Types/enums';

export type StatusRole = 'neutral' | 'info' | 'progress' | 'attention' | 'success' | 'danger';

export interface StatusPresentation {
  label: string;
  role: StatusRole;
  icon: LucideIcon;
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
export type TaskStatus = 'completed' | 'open' | 'due_soon' | 'overdue';
export type ContentStatus = 'published' | 'scheduled' | 'draft';
export type ProblemStatus = 'open' | 'in_progress' | 'resolved';
export type MissingVoteStatus = 'not_recorded';
export type UnknownBenefitStatus = 'unknown';

export const reservationResourceStatuses: Record<ReservationResourceStatus, StatusPresentation> = {
  created: status('Pateikta', 'info', Inbox),
  reserved: status('Rezervuota', 'success', CalendarCheck),
  lent: status('Paskolinta', 'progress', PackageOpen),
  returned: status('Grąžinta', 'neutral', RotateCcw),
  rejected: status('Atmesta', 'danger', CircleX),
  cancelled: status('Atšaukta', 'neutral', Ban),
};

/** Mirrors MeetingCompletionService::calculate(). A complete meeting is the healthy default and shows no badge in a collection. */
export const meetingCompletionStatuses: Record<MeetingCompletionStatus, StatusPresentation> = {
  complete: status('Užpildyta', 'success', CircleCheck),
  incomplete: status('Neužpildyta', 'attention', CircleDashed),
  no_items: status('Nėra darbotvarkės', 'attention', CircleSlash),
};

export const voteStatuses: Record<VoteValue | MissingVoteStatus, StatusPresentation> = {
  [VoteValue.Positive]: status('Už', 'success', CircleCheck),
  [VoteValue.Negative]: status('Prieš', 'danger', CircleX),
  [VoteValue.Neutral]: status('Susilaikė', 'neutral', CircleMinus),
  not_recorded: status('Nebalsuota', 'attention', CircleDashed),
};

export const studentBenefitStatuses: Record<VoteValue | UnknownBenefitStatus, StatusPresentation> = {
  [VoteValue.Positive]: status('Naudinga', 'success', ThumbsUp),
  [VoteValue.Negative]: status('Nenaudinga', 'danger', ThumbsDown),
  [VoteValue.Neutral]: status('Neutralu', 'neutral', CircleMinus),
  unknown: status('Nežinoma', 'attention', CircleDashed),
};

export const taskStatuses: Record<TaskStatus, StatusPresentation> = {
  completed: status('Atlikta', 'success', CircleCheck),
  open: status('Atvira', 'info', Circle),
  due_soon: status('Artėja terminas', 'attention', Clock3),
  overdue: status('Vėluoja', 'danger', TriangleAlert),
};

export const contentStatuses: Record<ContentStatus, StatusPresentation> = {
  published: status('Paskelbta', 'success', Eye),
  scheduled: status('Suplanuota', 'info', CalendarClock),
  draft: status('Juodraštis', 'neutral', FilePenLine),
};

export const problemStatuses: Record<ProblemStatus, StatusPresentation> = {
  open: status('Atvira', 'attention', CircleDot),
  in_progress: status('Vykdoma', 'progress', LoaderCircle),
  resolved: status('Išspręsta', 'success', CircleCheck),
};

export const supportRequestStatuses: Record<SupportRequestStatus, StatusPresentation> = {
  [SupportRequestStatus.New]: status('Naujas', 'info', Inbox),
  [SupportRequestStatus.Reviewing]: status('Peržiūrima', 'progress', Search),
  [SupportRequestStatus.Planned]: status('Suplanuota', 'info', CalendarClock),
  [SupportRequestStatus.InProgress]: status('Vykdoma', 'progress', LoaderCircle),
  [SupportRequestStatus.Done]: status('Išspręsta', 'success', CircleCheck),
  [SupportRequestStatus.Declined]: status('Atmesta', 'danger', CircleX),
};

export const institutionActivityStatuses: Record<InstitutionActivityStatus, StatusPresentation> = {
  [InstitutionActivityStatus.NoActivity]: status('Nėra veiklos', 'neutral', CircleSlash),
  [InstitutionActivityStatus.Healthy]: status('Aktyvi', 'success', CircleCheck),
  [InstitutionActivityStatus.Approaching]: status('Artėja terminas', 'attention', Clock3),
  [InstitutionActivityStatus.Overdue]: status('Vėluoja', 'danger', TriangleAlert),
  [InstitutionActivityStatus.CoveredByUpcomingMeeting]: status('Suplanuotas posėdis', 'info', CalendarClock),
  [InstitutionActivityStatus.CoveredByCheckIn]: status('Užfiksuotas kontaktas', 'info', CalendarCheck),
};

function status(label: string, role: StatusRole, icon: LucideIcon): StatusPresentation {
  return { label, role, icon };
}
