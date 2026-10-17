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
  /** The ended check-in after the last meeting; it pauses the count but never restarts it. */
  last_check_in_start?: string | null;
  last_check_in_end?: string | null;
}

export interface InstitutionActivityInsight extends InstitutionActivityStatus {
  id: string;
  name: string;
  /** Named only where several padaliniai share one list. */
  tenant_name?: string | null;
  /** The user is its secretary, not a member. */
  is_secretary?: boolean;
  is_administered?: boolean;
}
