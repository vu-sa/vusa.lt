import type { ActionType, InstitutionActivityAnswer, InstitutionActivityCampaign, InstitutionActivityStatus, InstitutionRelationKind, InstitutionScope, MeetingType } from '@/Types/enums';

export interface InstitutionUser {
  id: string;
  name: string;
  profile_photo_path: string | null;
  profile_photo_focal_point: string | null;
}

export interface InstitutionMemberTerm {
  duty_id: string;
  dutiable_id: string;
  dutiable_type: string;
  id: string;
  via_dutiable_id: string | null;
  tenant_id: number | null;
  start_date: string;
  end_date: string | null;
  additional_photo: string | null;
  additional_photo_focal_point: string | null;
  additional_email: string | null;
  use_original_duty_name: boolean;
  description: { lt: string; en: string } | null;
  study_program_id: string | null;
  study_program_note: { lt: string; en: string } | null;
  created_at: string;
  updated_at: string;
}

export interface InstitutionMember extends InstitutionUser {
  pivot: Pick<InstitutionMemberTerm, 'id' | 'start_date' | 'end_date'>;
}

export interface InstitutionPageDuty {
  id: string;
  name: string | null;
  description: string | null;
  institution_id: string;
  order: number;
  email: string | null;
  contacts_grouping: string;
  places_to_occupy: number | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  current_users: InstitutionMember[];
}

export interface InstitutionPageType {
  id: number;
  parent_id: number | null;
  title: string | null;
  description: string | null;
  slug: string | null;
  extra_attributes: Record<string, unknown> | null;
  created_at: string | null;
  updated_at: string | null;
  deleted_at: string | null;
  pivot: { institution_id: string; institution_type_id: number };
}

export interface InstitutionPageData {
  id: string;
  name: string;
  is_active: boolean;
  description: string | null;
  tenant: { id: number; shortname: string } | null;
  types: InstitutionPageType[];
  has_public_meetings: boolean;
  governance_scope: InstitutionScope;
  duties_count: number;
  meetings_count: number;
  problems_count: number;
  related_institutions_count: number;
  managers: InstitutionMember[];
  secretaries: InstitutionSecretary[];
  sharepointPath: string | null;
  sharepointFolderUrl: string | null;
}

export interface InstitutionPageMeeting {
  id: string;
  title: string;
  start_time: string;
  type: MeetingType | null;
  agenda_items_count: number;
  vote_matches: number;
  vote_mismatches: number;
  incomplete_vote_data: number;
  has_protocol: boolean;
  has_report: boolean;
  can_discard: boolean;
}

export interface InstitutionActivityData {
  status: InstitutionActivityStatus;
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
  last_completed_check_in_at: string | null;
}

export interface InstitutionOverviewData {
  activity_status: InstitutionActivityData | null;
  current_users: InstitutionMember[];
  recentMeetings: InstitutionPageMeeting[];
  meetings_count: number;
  meetings_hidden: boolean;
}

export interface InstitutionProblem {
  id: string;
  title: string;
  status: string;
  occurred_at: string;
  resolved_at: string | null;
  tenant: { id: number; shortname: string } | null;
  responsible_user: { id: string; name: string } | null;
}

export interface InstitutionPageRelatedInstitution {
  id: string;
  name: string | null;
  is_active: boolean;
  direction: 'outgoing' | 'incoming' | 'mutual';
  via: 'direct' | 'type';
  kind: InstitutionRelationKind;
  kind_label: string;
  cross_tenant: boolean;
  authorized: boolean;
}

export interface InstitutionLinkRow {
  id: number;
  created_at: string | null;
  updated_at: string | null;
  other: { id: string; name: string | null; is_active: boolean };
  direction: 'outgoing' | 'incoming';
  kind: InstitutionRelationKind;
  kind_label: string;
  mutual: boolean;
}

export interface InstitutionManagement {
  cadences: InstitutionCadence[];
  globalCadences: InstitutionCadence[];
  cadenceDefaults: { default_start_month_day: string; default_end_month_day: string };
  secretaryRosters: InstitutionSecretaryRoster[];
  studyPrograms: InstitutionStudyProgram[];
}

export interface InstitutionTask {
  id: string;
  name: string;
  description: string | null;
  due_date: string | null;
  completed_at: string | null;
  cancelled_at: string | null;
  created_at: string;
  updated_at: string;
  action_type: ActionType | null;
  metadata: Record<string, unknown> | null;
  progress: { current: number; total: number; percentage: number } | null;
  is_overdue: boolean;
  can_be_manually_completed: boolean;
  can_update: boolean;
  icon: string;
  color: string;
  taskable: { id: number | string; name: string | null; type: string } | null;
  taskable_type: string;
  taskable_id: string;
  users: ({ id: string; name: string; profile_photo_path: string | null })[];
}

export interface InstitutionHistoryItem {
  id: string;
  campaign_type: InstitutionActivityCampaign;
  requester: string | null;
  automatic: boolean;
  recipient: string | null;
  period_start: string;
  period_end: string | null;
  legacy: boolean;
  note: string | null;
  answer: InstitutionActivityAnswer | null;
  created_at: string | null;
  answered_at: string | null;
  resolved_at: string | null;
  expires_at: string;
  resolution_source: string | null;
  resolved_by: string | null;
  status: 'pending' | 'answered' | 'resolved' | 'expired';
  meetings: { id: string; date: string; url: string | null }[];
  check_ins: { id: string; start: string; end: string }[];
}

export interface InstitutionHistory {
  data: { id: string; campaigns: Record<string, InstitutionHistoryItem[]> }[];
  next_page: number | null;
}

export interface InstitutionCadence {
  id: string;
  institution_id: string | null;
  institution_name: string | null;
  start_date: string;
  end_date: string;
  start_meeting: { id: string; title: string; start_time: string; institution_id: string | null; institution_name: string | null } | null;
  end_meeting: { id: string; title: string; start_time: string; institution_id: string | null; institution_name: string | null } | null;
  label: string;
}

export interface InstitutionSecretaryRoster {
  cadence_id: string;
  label: string;
  start_date: string;
  end_date: string;
  is_global: boolean;
  is_current: boolean;
  is_past: boolean;
  secretaries: InstitutionSecretary[];
}

export interface InstitutionSecretary {
  id: string;
  name: string;
  email: string | null;
  profile_photo_path: string | null;
}

export interface InstitutionFile {
  id: string;
  fileable_type: string;
  fileable_id: string;
  sharepoint_id: string;
  sharepoint_path: string | null;
  name: string;
  file_type: string | null;
  mime_type: string | null;
  size_bytes: number | null;
  file_date: string | null;
  description: string | null;
  public_link: string | null;
  public_link_expires_at: string | null;
  last_synced_at: string | null;
  deleted_externally_at: string | null;
  created_at: string | null;
  updated_at: string | null;
  formatted_size: string | null;
}

export interface InstitutionStudyProgram {
  id: string;
  name: string;
  degree: string;
  tenant_id: number;
}

/** The props of `Pages/Admin/People/ShowInstitution.vue`, as `InstitutionController::show` sends them. */
export interface ShowInstitutionPageProps {
  institution: InstitutionPageData;
  overview: InstitutionOverviewData;
  activityRequests?: InstitutionHistory;
  canViewActivityRequests: boolean;
  can: { update: boolean; delete: boolean; recordMeeting: boolean; reportActivity: boolean; askAboutActivity: boolean; createProblem: boolean; manageLinks: boolean };
  duties?: InstitutionPageDuty[];
  problems?: InstitutionProblem[];
  tasks?: InstitutionTask[];
  incompleteTasksCount: number;
  assignmentTerms?: InstitutionMemberTerm[];
  relatedInstitutions?: InstitutionPageRelatedInstitution[];
  institutionLinks?: InstitutionLinkRow[];
  relationKinds: { value: InstitutionRelationKind; label: string }[];
  files?: InstitutionFile[];
  typeFiles?: (InstitutionFile & { fileable: { id: number; title: string | null } | null })[];
  management?: InstitutionManagement | null;
  subscription: { is_followed: boolean; is_duty_based: boolean } | null;
  readOnly: boolean;
}
