import type { DocumentStatus } from '@/Types/enums';

export interface FileableFileItem {
  id: string;
  name: string;
  file_type?: string | null;
  file_date?: string | null;
  formatted_size?: string | null;
  /** Set once someone has opened the file; anyone holding it can open the file until it is revoked. */
  public_link?: string | null;
  /** Loaded for type reference files, whose type title names where they come from. */
  fileable?: { id: string | number; title?: string | null } | null;
}

/** The managers' document view filter (`api.v1.admin.documents.folder`'s `show`). */
export type DocumentStatusFilter = 'all' | 'pending' | 'published' | 'hidden' | 'removed';

export type DocumentProblem = 'institution' | 'unknown_institution' | 'content_type' | 'document_date' | 'language';

/** A SharePoint archive file as the managers' views list it (DocumentRowResource). */
export interface DocumentFolderRow {
  id: number;
  title: string | null;
  name: string;
  status: DocumentStatus;
  content_type: string | null;
  language: string | null;
  document_date: string | null;
  institution: { id: string; name: string; tenant_shortname: string | null } | null;
  sharepoint_institution_label: string | null;
  sharepoint_path: string | null;
  sharepoint_web_url: string | null;
  sharepoint_modified_at: string | null;
  removed_from_sharepoint_at: string | null;
  problems: DocumentProblem[];
  sync_status: 'pending' | 'syncing' | 'success' | 'failed' | 'imported' | null;
  sync_error_message: string | null;
  /** vusa.lt short link, set only while the document is published and its SharePoint link exists. */
  public_url: string | null;
  can: { update: boolean };
}

/** One folder of the SharePoint document system, or every file below it (`api.v1.admin.documents.folder`). */
export interface DocumentFolderListing {
  path: string;
  breadcrumbs: { name: string; path: string }[];
  folders: { name: string; path: string; counts: Record<'published' | 'pending' | 'hidden', number> }[];
  files: DocumentFolderRow[];
  /** Ask with this offset for the next files; null when all are shown. */
  next_offset: number | null;
  /** Content types below this folder, for its filter; empty in search answers. */
  content_types: string[];
}
