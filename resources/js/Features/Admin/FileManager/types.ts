/**
 * Unified file types for FileManager component
 * Supports both local storage and SharePoint backends
 */

export type FileSource = 'local' | 'sharepoint';

/**
 * Unified file entry that works with both local and SharePoint files
 */
export interface UnifiedFile {
  /** Unique identifier - path for local, GUID for SharePoint */
  id: string;
  /** Storage path */
  path: string;
  /** File name */
  name: string;
  /** Whether this is a folder */
  isFolder: boolean;
  /** File size in bytes */
  size?: number;
  /** MIME type */
  mimeType?: string;
  /** Last modified date */
  modifiedAt?: Date;
  /** Created date */
  createdAt?: Date;
  /** Public URL for direct access */
  publicUrl?: string;
  /** SharePoint web URL (for opening in SharePoint) */
  webUrl?: string;
  /** Document metadata */
  metadata?: FileMetadata;
  /** Associated fileable entity */
  fileable?: FileableRef;
}

/**
 * Unified directory entry
 */
export interface UnifiedDirectory {
  /** Unique identifier - path for local, GUID for SharePoint */
  id: string;
  /** Storage path */
  path: string;
  /** Directory name */
  name: string;
  /** Always true for directories */
  isFolder: true;
}

/**
 * Document metadata stored in FileableFile for SharePoint files
 */
export interface FileMetadata {
  /** Document type (protocol, report, methodology, etc.) */
  type?: string;
  /** Document date */
  date?: Date;
  /** Description */
  description?: string;
}

/**
 * Reference to a fileable entity (duty, institution, meeting, type)
 */
export interface FileableRef {
  type: string;
  id: string | number;
}

/**
 * Response from file listing endpoints
 */
export interface FileListingResponse {
  files: UnifiedFile[];
  directories: UnifiedDirectory[];
  path: string;
  redirected?: boolean;
}

/**
 * Adapter interface for file operations
 * Implementations exist for local storage and SharePoint
 */
export interface FileSourceAdapter {
  /** Fetch files and directories at the given path */
  fetchListing(path: string): Promise<FileListingResponse>;
  
  /** Navigate to parent directory */
  getParentPath(currentPath: string): string;
  
  /** Check if path is at root level */
  isRootPath(path: string): boolean;
  
  /** Upload files to the given path */
  uploadFiles?(path: string, files: File[], fileable?: FileableRef): Promise<void>;
  
  /** Create a directory */
  createDirectory?(path: string, name: string): Promise<void>;
  
  /** Delete a file */
  deleteFile?(path: string): Promise<void>;
  
  /** Delete a directory (must be empty) */
  deleteDirectory?(path: string): Promise<void>;
  
  /** Open file (preview or download) */
  openFile(file: UnifiedFile): void;
}

/**
 * Props for the unified FileManager component
 */
export interface FileManagerProps {
  /** File source backend */
  source?: FileSource;
  /** Initial path to display */
  path?: string;
  /** Starting/root path (for SharePoint) */
  startingPath?: string;
  /** Associated fileable entity (for SharePoint uploads) */
  fileable?: FileableRef;
  /** Enable compact mode */
  small?: boolean;
  /** Enable file selection mode (for embedding) */
  selectionMode?: boolean;
  /** Allow upload UI in selection mode */
  allowUploadInSelection?: boolean;
  /** Restrict uploads to specific MIME types */
  uploadAccept?: string;
  /** Restrict uploads to specific extensions */
  uploadExtensions?: string[];
}

/**
 * Transform local FileEntry to UnifiedFile
 */
export function transformLocalFile(file: {
  path: string;
  name: string;
  size: number;
  modified: number;
  mimeType: string;
}): UnifiedFile {
  return {
    id: file.path,
    path: file.path,
    name: file.name,
    isFolder: false,
    size: file.size,
    mimeType: file.mimeType,
    modifiedAt: new Date(file.modified * 1000),
  };
}

/**
 * Transform local DirectoryEntry to UnifiedDirectory
 */
export function transformLocalDirectory(dir: {
  path: string;
  name: string;
}): UnifiedDirectory {
  return {
    id: dir.path,
    path: dir.path,
    name: dir.name,
    isFolder: true,
  };
}

/**
 * Transform SharePoint DriveItem to UnifiedFile
 */
export function transformSharepointFile(item: MyDriveItem, basePath: string): UnifiedFile {
  const itemName = item.name ?? '';
  const path = basePath ? `${basePath}/${itemName}` : itemName;
  
  return {
    id: item.id ?? path,
    path,
    name: itemName,
    isFolder: !!item.folder,
    size: item.size ?? undefined,
    mimeType: item.file?.mimeType ?? undefined,
    modifiedAt: item.lastModifiedDateTime ? new Date(item.lastModifiedDateTime) : undefined,
    createdAt: item.createdDateTime ? new Date(item.createdDateTime) : undefined,
    webUrl: item.webUrl ?? undefined,
    metadata: item.listItem?.fields ? {
      type: (item.listItem.fields as Record<string, unknown>).Type as string | undefined,
      date: (item.listItem.fields as Record<string, unknown>).Date 
        ? new Date((item.listItem.fields as Record<string, unknown>).Date as string) 
        : undefined,
      description: (item.listItem.fields as Record<string, unknown>).Description0 as string | undefined,
    } : undefined,
  };
}

/**
 * Transform SharePoint DriveItem to UnifiedDirectory
 */
export function transformSharepointDirectory(item: MyDriveItem, basePath: string): UnifiedDirectory {
  const itemName = item.name ?? '';
  const path = basePath ? `${basePath}/${itemName}` : itemName;
  
  return {
    id: item.id ?? path,
    path,
    name: itemName,
    isFolder: true,
  };
}
