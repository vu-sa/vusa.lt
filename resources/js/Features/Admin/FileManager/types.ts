export type FileKind = 'folder' | 'image' | 'document' | 'spreadsheet' | 'video' | 'audio' | 'archive' | 'other';
export type TypeFilter = 'all' | 'folder' | 'image' | 'document' | 'media';
export type SortKey = 'name' | 'modified' | 'size';
export type SortDir = 'asc' | 'desc';
export type ActiveView = 'browse' | 'starred' | 'recent';

export interface FileEntry {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType?: string;
  /** Parent directory — only set on recursive search results. */
  directory?: string;
  starred?: boolean;
  dimensions?: string;
  url?: string;
}

export interface DirectoryEntry {
  path: string;
  name: string;
  type: 'directory';
}

export interface ListingPayload {
  files: FileEntry[];
  directories: DirectoryEntry[];
  path: string;
  redirected?: boolean;
}
