export interface InstitutionSubscription {
  is_followed: boolean;
  is_muted: boolean;
  is_duty_based: boolean;
}

export type AtstovavimosUser = Omit<App.Entities.User, 'current_duties'> & {
  current_duties?: Array<{
    institution?: AtstovavimosInstitution;
  }>;
};

export interface AtstovavimosInstitution {
  id: string;
  name: string;
  tenant_id?: string | number;
  tenant?: {
    id: string | number;
    shortname: string;
    type?: string;
  };
  meetings?: AtstovavimosMeeting[];
  check_ins?: InstitutionCheckIn[];
  active_check_in?: InstitutionCheckIn | null;
  activity_status: InstitutionActivityStatus;
  hasUpcomingMeetings?: boolean;
  upcoming_meetings_count?: number;
  has_public_meetings?: boolean;
  // Subscription status for follow/mute UI
  subscription?: InstitutionSubscription;
  // Related institution metadata (only present for related institutions)
  is_related?: boolean;
  relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
  relationship_type?: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  source_institution_id?: string;
  // Whether the current user has authorization to access this institution's data
  // true for outgoing and sibling directions, false for incoming
  authorized?: boolean;
}

export interface InstitutionCheckIn {
  id: string;
  institution_id: string;
  user_id: string;
  start_date: string;
  end_date: string;
  note?: string;
}

export interface AtstovavimosMeeting {
  id: string;
  start_time: string;
  institution_id?: string;
  completion_status?: 'complete' | 'incomplete' | 'no_items';
  has_report?: boolean;
  has_protocol?: boolean;
  type?: string;
  type_slug?: string;
  agenda_items?: AtstovavimosAgendaItem[];
  institutions?: Array<{
    id: string;
    name: string;
    has_public_meetings?: boolean;
  }>;
  // Meeting types for icon differentiation
  types?: Array<{
    id: string | number;
    slug: string;
  }>;
}

export interface AtstovavimosAgendaItem {
  id: string | number;
  title?: string;
  type?: string | null;
  main_vote?: AtstovavimosVote | null;
  votes?: AtstovavimosVote[];
}

export interface AtstovavimosVote {
  is_main?: boolean;
  student_vote?: 'positive' | 'negative' | 'neutral' | null;
  decision?: 'positive' | 'negative' | 'neutral' | null;
}

export interface AtstovavimosGap {
  institution_id: string;
  from: Date;
  until: Date;
  mode?: 'heads_up' | 'no_meetings';
  note?: string;
}

export interface AtstovavimosTenant {
  id: string | number;
  shortname: string;
  type: string;
}

export interface InstitutionInsights {
  attention: InstitutionActivityInsight[];
}

// Agenda item for Gantt tooltip display
export interface GanttAgendaItem {
  id: string;
  title: string;
  student_vote?: 'positive' | 'negative' | 'neutral' | null;
  decision?: 'positive' | 'negative' | 'neutral' | null;
}

export interface GanttMeeting {
  id: string;
  start_time: Date;
  institution_id: string;
  institution: string;
  completion_status?: 'complete' | 'incomplete' | 'no_items';
  // Agenda items for tooltip display (limited to first 4)
  agenda_items?: GanttAgendaItem[];
  agenda_items_count?: number;
  // Whether the user has authorization for this meeting's institution
  authorized?: boolean;
  // File status indicators for tooltip display
  has_report?: boolean;
  has_protocol?: boolean;
  // Meeting type slug for icon differentiation (in-person-meeting, remote-meeting, email-meeting)
  type_slug?: string;
}

export interface GanttInstitution {
  id: string;
  name: string;
  tenant_id?: string;
  has_public_meetings?: boolean;
  // Related institution metadata
  is_related?: boolean;
  relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
  relationship_type?: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  source_institution_id?: string;
  // Whether the current user has authorization to access this institution's data
  authorized?: boolean;
}

// Duty member for Gantt chart display
export interface GanttDutyMember {
  institution_id: string;
  duty_id: string;
  user: {
    id: string;
    name: string;
    profile_photo_path?: string | null;
    // Activity status (only available in tenant view, enriched from representativeActivity)
    activityCategory?: RepresentativeActivityCategory;
    lastAction?: string | null;
  };
  start_date: Date;
  end_date?: Date | null;
}

// Period when institution had no active duty members
export interface InactivePeriod {
  institution_id: string;
  from: Date;
  until: Date;
}

// Representative activity tracking types
export type RepresentativeActivityCategory = 'today' | 'week' | 'month' | 'stale' | 'never';

export interface RepresentativeUser {
  id: string;
  name: string;
  email: string;
  profile_photo_path?: string | null;
  last_action: string | null;
  category: RepresentativeActivityCategory;
  duties: Array<{
    id: string;
    name: string;
    institution_name?: string;
  }>;
}

export interface RepresentativeActivityStats {
  total: number;
  activeToday: number;
  activeLast7Days: number;
  activeLast30Days: number;
  neverLoggedIn: number;
}

export interface RepresentativeActivityData {
  stats: RepresentativeActivityStats;
  preview_users: RepresentativeUser[];
}

export interface InstitutionStatusSummaryData {
  all: number;
  needs_attention: number;
  overdue: number;
  approaching: number;
  no_activity: number;
  current: number;
}

export interface RepresentativePagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface RepresentativePageData {
  users: RepresentativeUser[];
  pagination: RepresentativePagination;
}

export interface AtstovavimosTenantTimelineData {
  institutions: AtstovavimosInstitution[];
  related_institutions: AtstovavimosInstitution[];
  institution_summary: InstitutionStatusSummaryData;
  representative_activity: RepresentativeActivityData;
}
import type {
  InstitutionActivityInsight,
  InstitutionActivityStatus,
} from '@/Types/InstitutionActivity';
