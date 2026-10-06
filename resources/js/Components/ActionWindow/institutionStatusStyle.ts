import {
  CalendarCheck,
  CalendarClock,
  CalendarOff,
  CircleAlert,
  CircleHelp,
  Landmark,
  type LucideIcon,
} from 'lucide-vue-next';

import type { StatusRole } from '@/Constants/statuses';

export interface InstitutionStatusStyle {
  icon: LucideIcon;
  tone: StatusRole;
}

/**
 * Each activity status gets its own icon and status role: "overdue" and "covered by a
 * check-in" are opposite situations, and a single warning triangle for both was the
 * fastest way to make the list unreadable. Roles follow `institutionActivityStatuses`.
 */
const STATUS_STYLES: Record<string, InstitutionStatusStyle> = {
  overdue: { icon: CircleAlert, tone: 'danger' },
  approaching: { icon: CalendarClock, tone: 'attention' },
  no_activity: { icon: CircleHelp, tone: 'neutral' },
  covered_by_check_in: { icon: CalendarOff, tone: 'info' },
  covered_by_upcoming_meeting: { icon: CalendarCheck, tone: 'info' },
  // The healthy state is the ordinary one and carries no colour (status rule: don't paint every row).
  healthy: { icon: Landmark, tone: 'neutral' },
};

const FALLBACK_STYLE: InstitutionStatusStyle = { icon: Landmark, tone: 'neutral' };

export function institutionStatusStyle(status: string | undefined): InstitutionStatusStyle {
  return (status && STATUS_STYLES[status]) || FALLBACK_STYLE;
}
