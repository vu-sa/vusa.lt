export interface AssignmentDuty {
  id: string | number;
  name?: unknown;
  institution?: { name?: unknown } | null;
  types?: { id?: number | string | null }[] | null;
}

export interface AssignmentUser {
  id: string | number;
  name?: string | null;
  email?: string | null;
  profile_photo_path?: string | null;
  profile_photo_focal_point?: string | null;
}

export interface AssignmentTerm {
  created_at?: string | null;
  updated_at?: string | null;
  id: string;
  duty_id?: string | number;
  dutiable_id?: string | number;
  via_dutiable_id?: string | null;
  tenant_id?: number | null;
  start_date?: string | null;
  end_date?: string | null;
  study_program_id?: string | null;
  study_program_note?: unknown;
  description?: unknown;
  additional_email?: string | null;
  additional_photo?: string | null;
  additional_photo_focal_point?: string | null;
  use_original_duty_name?: boolean;
  duty?: AssignmentDuty | null;
  user?: AssignmentUser | null;
  dutiable?: AssignmentUser | null;
}

export interface UserWithPivot {
  id: string | number;
  name: string;
  profile_photo_path?: string | null;
  pivot?: AssignmentTerm | null;
}

export interface DutyWithUsers {
  id: string | number;
  name?: string | { lt?: string; en?: string } | null;
  order?: number | null;
  current_users?: UserWithPivot[];
  previous_users?: UserWithPivot[];
}
