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

export interface HomeChecklistItem {
  key: 'photo' | 'follow' | 'notifications' | 'meeting';
  done: boolean;
  /** Null when the item opens the ActionWindow instead of a page. */
  href: string | null;
}

/** The first-login checklist (U13); the server sends null once it no longer applies. */
export interface HomeChecklist {
  items: HomeChecklistItem[];
  doneCount: number;
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
