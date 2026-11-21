import type { Component } from 'vue';

import type { FileKind } from '@/Utils/fileIcons';

export type TypeFilter = 'all' | 'folder' | Exclude<FileKind, 'folder'>;
export type SortKey = 'name' | 'modified' | 'size';
export type SortDir = 'asc' | 'desc';
export type ActiveView = 'browse' | 'starred' | 'recent';
export type FileViewMode = 'cards' | 'table';

export interface FileEntry {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType?: string;
  /** Parent directory — only set on recursive search results. */
  directory?: string;
  url?: string;
}

export interface DirectoryEntry {
  path: string;
  name: string;
  type: 'directory';
  /** Files and folders inside; only an empty folder can be deleted. */
  items?: number;
}

/** What the actor may do in the open folder; actions they lack are hidden, not disabled. */
export interface FileManagerAbilities {
  upload: boolean;
  createDirectory: boolean;
  deleteDirectory: boolean;
  update: boolean;
  delete: boolean;
}

export interface ListingPayload {
  files: FileEntry[];
  directories: DirectoryEntry[];
  path: string;
  redirected?: boolean;
}

/** A row in a file or folder ⋯ menu. */
export interface ItemAction {
  key: string;
  label: string;
  icon: Component;
  destructive?: boolean;
}

/** The view the URL can carry, so a link or the Back button restores it. */
export interface FileManagerViewState {
  q: string;
  type: TypeFilter;
  sort: `${SortKey}:${SortDir}`;
  view: FileViewMode;
  file: string | null;
}
