import type { MeetingMissingAction } from '@/Components/Meetings/MeetingCompletionChecklist.vue';
import type { MeetingCompletionStatus } from '@/Constants/statusIcons';
import type { AgendaItemType, MeetingKind, MeetingType } from '@/Types/enums';

/** ShowMeeting's payload, as `MeetingPresenter` / `MeetingPanelPresenter` build it. */
export interface MeetingInstitution {
  id: string;
  name: string | null;
  is_active: boolean;
  tenant_id: number | null;
  tenant: { shortname: string } | null;
}

export interface MeetingFile {
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

export interface MeetingVote {
  is_main: boolean;
  is_consensus: boolean;
  student_vote: string | null;
  decision: string | null;
  student_benefit: string | null;
}

export interface RedactedAgendaItem {
  id: string;
  order: number;
  is_private: true;
  is_redacted: true;
  title: string;
  can: { update: boolean; delete: boolean };
}

export interface FullAgendaItem {
  is_redacted?: never;
  id: string;
  order: number;
  brought_by_students: boolean;
  type: AgendaItemType | null;
  start_time: string | null;
  end_time: string | null;
  title: string | null;
  is_private: boolean;
  votes: MeetingVote[];
  comments_count: number;
  has_notes: boolean;
  can: { update: boolean; delete: boolean };
}

export type AgendaListItem = RedactedAgendaItem | FullAgendaItem;

export interface MeetingRecord {
  id: string;
  title: string;
  meeting_kind: MeetingKind | null;
  type: MeetingType | null;
  type_label: string | null;
  start_time: string;
  end_time: string | null;
  created_at: string;
  updated_at: string;
  description: { lt: string; en: string };
  title_noun: string;
  inherited_meeting_kind: MeetingKind;
  agenda_items: AgendaListItem[];
  sharepointPath: string | null;
  sharepointFolderUrl: string | null;
  institutions: MeetingInstitution[];
  has_calendar_event: boolean;
  has_protocol: boolean;
}

export interface MeetingRepresentative {
  id: string;
  name: string;
  email: string | null;
  phone: string | null;
  facebook_url: string | null;
  pronouns: string | null;
  show_pronouns: boolean;
  profile_photo_path: string | null;
  profile_photo_focal_point: string | null;
}


export interface AttachableMeetingInstitution {
  id: string;
  name: string | null;
  is_active: boolean;
  tenant_shortname: string | null;
}

export interface LinkedMeetingDocument {
  id: number;
  title: string;
  name: string;
  content_type: string | null;
  document_date: string | null;
  language_code: string;
  anonymous_url: string | null;
}

export interface MeetingAbilities {
  update: boolean;
  delete: boolean;
  discard: boolean;
  createAgendaItems: boolean;
  reorderAgendaItems: boolean;
  attachInstitution: boolean;
}

export interface MeetingCompletion {
  status: MeetingCompletionStatus | null;
  missingActions: MeetingMissingAction[];
}
