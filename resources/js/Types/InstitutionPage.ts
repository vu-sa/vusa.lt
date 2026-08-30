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

export interface InstitutionPageMeeting extends App.Entities.Meeting {
  vote_matches?: number;
  vote_mismatches?: number;
  incomplete_vote_data?: number;
  /** First few agenda item titles; the full relation is too heavy to send. */
  agenda_item_titles?: string[];
  agenda_items_count?: number;
}

export interface InstitutionPageTask {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  action_type?: string | null;
  progress?: {
    current: number;
    total: number;
    percentage: number;
  } | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
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

export type InstitutionPageData = Omit<
  App.Entities.Institution,
  'name' | 'short_name' | 'description' | 'duties' | 'meetings' | 'types' | 'tasks'
> & {
  name: string;
  short_name?: string | null;
  description?: string | null;
  duties: InstitutionPageDuty[];
  meetings: InstitutionPageMeeting[];
  types: InstitutionPageType[];
  current_users: App.Entities.User[];
  managers: App.Entities.User[];
  /** Nominated for the current term. Not members — see InstitutionAdministrator. */
  administrators: AdministratorUser[];
  allTasks: InstitutionPageTask[];
  recentComments: InstitutionPageComment[];
  relatedInstitutions: Record<string, InstitutionPageRelatedInstitution[]>;
  relatedInstitutionsFlat: InstitutionPageRelatedInstitution[];
  sharepointPath: string | null;
  activity_status: InstitutionActivityStatus;
};
