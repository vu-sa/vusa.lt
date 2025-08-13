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
  active_check_in?: {
    id: string;
    until_date: string;
    mode: 'heads_up' | 'no_meetings';
    state: string;
  };
  hasUpcomingMeetings?: boolean;
  upcoming_meetings_count?: number;
  days_since_last_meeting?: number;
}

export interface AtstovavimosMeeting {
  id: string;
  start_time: string;
  institution_id?: string;
  institutions?: Array<{
    id: string;
    name: string;
  }>;
}

export interface AtstovavimosGap {
  institution_id: string;
  from: Date;
  until: Date;
  mode?: 'heads_up' | 'no_meetings';
}

export interface AtstovavimosTenant {
  id: number;
  shortname: string;
  type: string;
}

export interface TimelineFilters {
  userTenantFilter: string[];
  showOnlyWithActivityUser: boolean;
  showOnlyWithActivityTenant: boolean;
  selectedTenantForGantt: string[]; // Changed to array for multiple selection
}

export interface InstitutionInsights {
  withoutMeetings: AtstovavimosInstitution[];
  withOldMeetings: Array<AtstovavimosInstitution & {
    lastMeetingDate: Date;
    daysSinceLastMeeting: number;
  }>;
}

export interface GanttMeeting {
  id: string;
  start_time: Date;
  institution_id: string;
  institution: string;
}

export interface GanttInstitution {
  id: string;
  name: string;
  tenant_id?: string;
}

export interface ModalState {
  showMeetingModal: boolean;
  showAllMeetingModal: boolean;
  showAllInstitutionModal: boolean;
  showCreateCheckIn: { open: boolean; institutionId?: string } | null;
  showFullscreenGantt: boolean;
  fullscreenGanttType: 'user' | 'tenant';
  selectedInstitution?: any;
  selectedSuggestedAt?: Date;
}
