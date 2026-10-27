import type {
  MailingListAddressMode,
  MailingListMode,
  MailingListSourceType,
  MailingListSyncStatus,
  MailingListTenantScope,
  TenantType,
} from '@/Types/enums';

export interface MailingListSummary {
  created_at?: string | null;
  updated_at?: string | null;
  id: string;
  address: string;
  display_name: string;
  description: string | null;
  mode: MailingListMode;
  tenant: { id: number; shortname: string } | null;
  archive_policy: string | null;
  member_count: number;
  posts_last_year: number | null;
  last_post_at: string | null;
  last_synced_at: string | null;
  last_sync_status: MailingListSyncStatus | null;
}

export interface MailingListSourceItem {
  id: string;
  type: MailingListSourceType;
  label: string;
  address_mode: MailingListAddressMode | null;
  tenant_scope: MailingListTenantScope;
  tenants: string[];
  include_subtypes: boolean;
  excluded_types: string[];
  duty_types: string[];
  note: string | null;
  expires_on: string | null;
  is_expired: boolean;
}

export interface MailingListExclusionItem {
  id: string;
  email: string;
  note: string | null;
}

export interface MailingListRosterEntry {
  email: string;
  display_name: string | null;
  reasons: string[] | null;
}

export interface MailingListDiffPayload {
  add?: { email: string; display_name: string | null; reasons: string[] }[];
  remove?: { email: string; display_name: string | null }[];
  desired_count?: number;
  current_count?: number;
  needs_review?: boolean;
  error?: string;
  preview_token?: string;
}

export interface MailingListMessageItem {
  id: number;
  subject: string | null;
  sender_name: string | null;
  sender_email: string | null;
  sent_at: string | null;
  body_text: string | null;
  attachments: { name: string; size: number | null; url: string | null }[];
  url: string | null;
}

interface TypeOption {
  id: number;
  title: string;
  parent_id: number | null;
}

export interface MailingListSourceOptions {
  globalScope: boolean;
  tenants: { id: number; shortname: string; type: TenantType | null }[];
  institutionTypes: TypeOption[];
  dutyTypes: TypeOption[];
  institutions: { id: string; name: string }[];
  mailingLists: { id: string; address: string }[];
  responsibilities: { id: string; title: string; purpose: string | null }[];
}

export interface MailingListArchivePage {
  data: MailingListMessageItem[];
  current_page: number;
  last_page: number;
  total: number;
}
