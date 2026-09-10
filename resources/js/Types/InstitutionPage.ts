import type { AdministratorUser } from '@/Components/Institutions';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

export interface InstitutionPageComment {
  id: string;
  body: string;
  kind?: string;
  created_at: string;
  replies_count?: number;
  user?: {
    id: string | number;
    name: string;
    profile_photo_path?: string | null;
  } | null;
}

export interface InstitutionPageDuty {
  id: string | number;
  name: string;
  email?: string | null;
  order?: number | null;
  places_to_occupy?: number | null;
  current_users?: App.Entities.User[];
}

export interface InstitutionPageMeeting {
  id: string;
  title: string;
  start_time: string;
  type?: string | null;
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  agenda_item_titles?: string[];
  agenda_items_count?: number;
  has_protocol?: boolean;
  has_report?: boolean;
}

export interface InstitutionPageTask {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  created_at?: string | null;
  action_type?: string | null;
  metadata?: Record<string, unknown> | null;
  progress?: {
    current: number;
    total: number;
    percentage: number;
  } | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  icon?: string | null;
  color?: string | null;
  taskable?: {
    id: string;
    name?: string;
    type?: string;
  } | null;
  taskable_type: string;
  taskable_id: string;
  users?: App.Entities.User[];
}

export interface InstitutionPageRelatedInstitution {
  id: string | number;
  name: string;
  direction?: 'outgoing' | 'incoming' | 'sibling';
  type?: 'direct' | 'type-based' | 'within-type' | 'cross-tenant-sibling';
  authorized?: boolean;
}

interface InstitutionPageType extends Omit<App.Entities.Type, 'title'> {
  title?: string | null;
}

export interface InstitutionPageData {
  id: string | number;
  name: string;
  short_name?: string | null;
  description?: string | null;
  types: InstitutionPageType[];
  managers: App.Entities.User[];
  administrators: AdministratorUser[];
  sharepointPath: string | null;
  has_public_meetings?: boolean;
  meeting_periodicity_days?: number | null;
  governance_scope?: string;
  comments_count?: number;
  duties_count: number;
  meetings_count: number;
  tasks_count: number;
  related_institutions_count: number;
}

export interface InstitutionOverviewData {
  activity_status: InstitutionActivityStatus;
  current_users: App.Entities.User[];
  duties: InstitutionPageDuty[];
  recentMeetings: InstitutionPageMeeting[];
  meetings_count: number;
  recentComments: InstitutionPageComment[];
};
