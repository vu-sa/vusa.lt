import type { LucideIcon } from 'lucide-vue-next';
import {
  Ban,
  CalendarCheck,
  CalendarClock,
  Circle,
  CircleCheck,
  CircleDashed,
  CircleMinus,
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

import { SupportRequestStatus, VoteValue } from '@/Types/enums';

export type StatusRole = 'neutral' | 'info' | 'progress' | 'attention' | 'success' | 'danger';

export interface StatusPresentation {
  label: string;
  role: StatusRole;
  icon: LucideIcon;
}

export type ReservationResourceStatus = 'created' | 'reserved' | 'lent' | 'returned' | 'rejected' | 'cancelled';
export type TaskStatus = 'completed' | 'open' | 'due_soon' | 'overdue';
export type ContentStatus = 'published' | 'scheduled' | 'draft';
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

export const supportRequestStatuses: Record<SupportRequestStatus, StatusPresentation> = {
  [SupportRequestStatus.New]: status('Naujas', 'info', Inbox),
  [SupportRequestStatus.Reviewing]: status('Peržiūrima', 'progress', Search),
  [SupportRequestStatus.Planned]: status('Suplanuota', 'info', CalendarClock),
  [SupportRequestStatus.InProgress]: status('Vykdoma', 'progress', LoaderCircle),
  [SupportRequestStatus.Done]: status('Išspręsta', 'success', CircleCheck),
  [SupportRequestStatus.Declined]: status('Atmesta', 'danger', CircleX),
};

function status(label: string, role: StatusRole, icon: LucideIcon): StatusPresentation {
  return { label, role, icon };
}
