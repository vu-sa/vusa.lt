import type { InstitutionActivityInsight, InstitutionActivityStatusName } from '@/Types/InstitutionActivity';

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
  institution_id?: string | null;
  tenant_id?: number | null;
  /** Reached only through a followed institution, not one of the user's duties. */
  is_followed?: boolean;
}

/** A row of "Sekamos institucijos" (GetFollowedInstitutions). */
export interface HomeFollowedInstitution {
  id: string;
  name: string;
  is_muted: boolean;
  activity_status: InstitutionActivityStatusName;
}

export interface HomeFollowedInstitutions {
  items: HomeFollowedInstitution[];
  total: number;
}

export interface HomeRecentRecord {
  type: string;
  id: string;
  title: string;
  href: string;
  changed_at: string;
}

export interface HomeCoordinator {
  id?: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
  duty: string | null;
  /** The rep's institutions this coordinator covers (GetUserCoordinators). */
  institutions?: string[];
}

export interface HomeNewsPreview {
  id: number;
  title: string;
  lang: string;
  permalink: string | null;
  image: string | null;
  publish_time: string;
  public_url: string | null;
}

export interface HomeHeroImage {
  url: string;
  focalPoint: string | null;
}

/** A duty term that began or ended lately (U14), newest first. */
export interface HomeAccessChange {
  kind: 'started' | 'ended';
  dutyName: string;
  institutionName: string | null;
  date: string;
  effectiveOn: string;
  isExOfficio: boolean;
}

export interface HomeRegistrationForm {
  key: 'member' | 'student_rep';
  href: string;
}
