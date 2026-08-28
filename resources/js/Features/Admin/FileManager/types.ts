export interface FileEntry {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType?: string;
  /** Parent directory — only set on recursive search results. */
  directory?: string;
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
