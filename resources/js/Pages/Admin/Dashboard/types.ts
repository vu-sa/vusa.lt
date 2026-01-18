export interface InstitutionSubscription {
  is_followed: boolean;
  is_muted: boolean;
  is_duty_based: boolean;
}

export interface AtstovavimosUser extends App.Entities.User {
  current_duties?: Array<{
    institution?: AtstovavimosInstitution;
  }>;
}

export interface AtstovavimosInstitution {
  id: string;
  name: string;
  tenant?: {
    id: string;
    shortname: string;
  };
  meetings?: AtstovavimosMeeting[];
  check_ins?: InstitutionCheckIn[];
  active_check_in?: InstitutionCheckIn | null;
  hasUpcomingMeetings?: boolean;
  upcoming_meetings_count?: number;
  days_since_last_meeting?: number;
  has_public_meetings?: boolean;
  // Subscription status for follow/mute UI
  subscription?: InstitutionSubscription;
  // Related institution metadata (only present for related institutions)
  is_related?: boolean;
  relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
  relationship_type?: 'direct' | 'type-based' | 'within-type';
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
  institutions?: Array<{
    id: string;
    name: string;
    has_public_meetings?: boolean;
  }>;
}

export interface AtstovavimosGap {
  institution_id: string;
  from: Date;
  until: Date;
  mode?: 'heads_up' | 'no_meetings';
  note?: string;
}

export interface AtstovavimosTenant {
  id: number;
  shortname: string;
  type: string;
}

export interface InstitutionInsights {
  withoutMeetings: AtstovavimosInstitution[];
  withOldMeetings: Array<AtstovavimosInstitution & {
    lastMeetingDate: Date;
    daysSinceLastMeeting: number;
    periodicity: number;
    isOverdue: boolean;
    isApproaching: boolean;
  }>;
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
}

export interface GanttInstitution {
  id: string;
  name: string;
  tenant_id?: string;
  has_public_meetings?: boolean;
  // Related institution metadata
  is_related?: boolean;
  relationship_direction?: 'outgoing' | 'incoming' | 'sibling';
  relationship_type?: 'direct' | 'type-based' | 'within-type';
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

