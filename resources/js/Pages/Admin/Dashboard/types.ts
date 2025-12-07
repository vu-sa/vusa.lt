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
  hasUpcomingMeetings?: boolean;
  upcoming_meetings_count?: number;
  days_since_last_meeting?: number;
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
  completion_status?: 'complete' | 'incomplete' | 'no_items';
}

export interface GanttInstitution {
  id: string;
  name: string;
  tenant_id?: string;
}
