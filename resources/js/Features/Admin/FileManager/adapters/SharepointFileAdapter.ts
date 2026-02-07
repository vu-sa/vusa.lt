import { useFetch } from '@vueuse/core';
import type { 
  FileSourceAdapter, 
  FileListingResponse, 
  UnifiedFile, 
  UnifiedDirectory,
  FileableRef 
} from '../types';
import { transformSharepointFile, transformSharepointDirectory } from '../types';

/**
 * Adapter for SharePoint files via Microsoft Graph API
 * Files remain in SharePoint, metadata cached in FileableFile model
 */
export class SharepointFileAdapter implements FileSourceAdapter {
  private startingPath: string;
  private fileable?: FileableRef;

  constructor(startingPath: string, fileable?: FileableRef) {
    this.startingPath = startingPath;
    this.fileable = fileable;
  }

  async fetchListing(path: string): Promise<FileListingResponse> {
    const { data } = await useFetch(
      route('api.v1.admin.sharepoint.driveItems', { path })
    ).get().json();

    // Handle standardized API response format
    const responseData = data.value?.success ? data.value.data : data.value;
    const items: MyDriveItem[] = responseData ?? [];
    
    const files: UnifiedFile[] = items
      .filter(item => !item.folder)
      .map(item => transformSharepointFile(item, path));

    const directories: UnifiedDirectory[] = items
      .filter(item => !!item.folder)
      .map(item => transformSharepointDirectory(item, path));

    return {
      files,
      directories,
      path,
    };
  }

  getParentPath(currentPath: string): string {
    const segments = currentPath.split('/');
    if (segments.length > 1) segments.pop();
    return segments.join('/');
  }

  isRootPath(path: string): boolean {
    return path === this.startingPath;
  }

  openFile(file: UnifiedFile): void {
    // Open in SharePoint via webUrl
    if (file.webUrl) {
      window.open(file.webUrl, '_blank');
    }
  }

  /**
   * Get the starting path for this adapter
   */
  getStartingPath(): string {
    return this.startingPath;
  }

  /**
   * Get the associated fileable entity
   */
  getFileable(): FileableRef | undefined {
    return this.fileable;
  }
}
