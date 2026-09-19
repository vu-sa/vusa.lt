import type { InstitutionActivityInsight } from '@/Types/InstitutionActivity';

export type { InstitutionActivityInsight };

/** A pending task as the dashboard payload carries it (DashboardController::index). */
export interface HomeTask {
  id: string;
  name: string;
  due_date: string | null;
  is_overdue: boolean;
  taskable_type: string;
  taskable_id: string;
  taskable?: { id: string; name: string | null } | null;
  action_type?: string | null;
}

export interface HomeMeeting {
  id: string;
  title: string;
  start_time: string;
  institution_name: string | null;
}

export interface HomeRecentRecord {
  type: string;
  id: string;
  title: string;
  href: string;
  changed_at: string;
}

export interface HomeCoordinator {
  name: string;
  email: string | null;
  profile_photo_path: string | null;
  duty: string | null;
}

export interface HomeContentItem {
  id: string;
  title: string;
  date: string | null;
}
