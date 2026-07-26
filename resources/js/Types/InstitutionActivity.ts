export type InstitutionActivityStatusName
  = | 'no_activity'
    | 'healthy'
    | 'approaching'
    | 'overdue'
    | 'covered_by_upcoming_meeting'
    | 'covered_by_check_in';

export interface InstitutionActivityStatus {
  status: InstitutionActivityStatusName;
  requires_action: boolean;
  priority: number;
  periodicity_days: number;
  effective_days_since_activity: number | null;
  progress_percentage: number | null;
  last_activity_type: 'meeting' | 'check_in' | null;
  last_activity_at: string | null;
  last_meeting_at: string | null;
  next_meeting_at: string | null;
  active_check_in_until: string | null;
}

export interface InstitutionActivityInsight extends InstitutionActivityStatus {
  id: string;
  name: string;
}
