import type { AvatarUser } from '@/Components/Avatars/UserAvatar.vue';
import type { SecretaryUser } from '@/Components/Institutions';
import type { MeetingSearchResult } from '@/Shared/Search/types';
import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

export interface InstitutionPageMeeting {
  id: string;
  title: string;
  start_time: string;
  type?: string | null;
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  /** `Meeting::outcomeSummary()` for the reader, as the meetings index shows it. */
  outcome?: Partial<MeetingSearchResult>;
  agenda_items_count?: number;
  has_protocol?: boolean;
  has_report?: boolean;
  can_discard?: boolean;
}

export interface InstitutionPageTask {
  id: string;
  name: string | null;
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
  users?: AvatarUser[];
}

export interface InstitutionPageRelatedInstitution {
  is_active?: boolean;
  id: string | number;
  name: string | null;
  direction?: 'outgoing' | 'incoming' | 'mutual';
  via?: 'direct' | 'type';
  kind?: string;
  kind_label?: string;
  cross_tenant?: boolean;
  authorized?: boolean;
}

interface InstitutionPageType {
  id: number;
  title?: string | null;
}

export interface InstitutionPageData {
  is_active: boolean;
  id: string | number;
  name: string;
  description?: string | null;
  types: InstitutionPageType[];
  managers: AvatarUser[];
  secretaries?: SecretaryUser[];
  sharepointPath: string | null;
  sharepointFolderUrl?: string | null;
  has_public_meetings?: boolean;
  governance_scope?: string;
  duties_count: number;
  meetings_count: number;
  problems_count?: number;
  related_institutions_count: number;
  /** Own or type files exist; without them the Failai tab gives way to an "add" action. */
  has_files?: boolean;
}

export interface InstitutionOverviewData {
  /** Null when withheld: the meetings it is read off are not public and the reader has no access. */
  activity_status: InstitutionActivityStatus | null;
  current_users: AvatarUser[];
  recentMeetings: InstitutionPageMeeting[];
  meetings_count: number;
  /** The institution has meetings, but they are not public and the reader has no access. */
  meetings_hidden?: boolean;
};
