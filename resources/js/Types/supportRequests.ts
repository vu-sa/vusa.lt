export interface SupportRequestUser {
  id: string;
  name: string;
  email?: string;
  profile_photo_path?: string | null;
}

export interface SupportRequestRoleOption {
  id: string;
  name: string;
  users: SupportRequestUser[];
}

export interface SupportRequestTaxonomyItem {
  id: number;
  name: string | Record<string, string>;
  slug?: string;
}

export interface SupportRequestMediaFile {
  id: number;
  name: string;
  file_name: string;
  mime_type: string;
  size: number;
  original_url: string;
  thumb_url?: string;
  preview_url?: string;
}

export interface SupportRequestItem {
  id: string;
  created_by?: string | null;
  assigned_to?: string | null;
  support_service_id: number;
  support_request_type_id: number;
  support_request_area_id: number;
  reporter_name?: string | null;
  reporter_email?: string | null;
  visibility: 'private' | 'roles' | 'public' | { value: string; label?: string };
  status: 'new' | 'reviewing' | 'planned' | 'in_progress' | 'done' | 'declined' | { value: string; label?: string };
  title: string;
  description: string;
  context_url?: string | null;
  selected_text?: string | null;
  locale?: string;
  resolved_at?: string | null;
  created_at: string;
  updated_at?: string;
  creator?: SupportRequestUser | null;
  assignedTo?: SupportRequestUser | null;
  type?: SupportRequestTaxonomyItem;
  area?: SupportRequestTaxonomyItem;
  service?: SupportRequestTaxonomyItem;
  roles?: Array<{ id: string; name: string }>;
  role_users?: SupportRequestUser[];
  media?: SupportRequestMediaFile[];
  comments_count?: number;
}
