import { useFetch } from '@vueuse/core';
import type { 
  FileSourceAdapter, 
  FileListingResponse, 
  UnifiedFile, 
  UnifiedDirectory,
  FileableRef,
  transformLocalFile, 
  transformLocalDirectory 
} from '../types';

/**
 * Adapter for local storage files
 * Uses Laravel's filesystem via files.* routes
 */
export class LocalFileAdapter implements FileSourceAdapter {
  private rootPath: string;

  constructor(rootPath = 'public/files') {
    this.rootPath = rootPath;
  }

  async fetchListing(path: string): Promise<FileListingResponse> {
    const { data } = await useFetch(route('api.v1.admin.files.index', { path })).get().json();
    
    // Handle standardized API response format
    const responseData = data.value?.success ? data.value.data : data.value;
    
    const files: UnifiedFile[] = (responseData?.files ?? []).map((file: {
      path: string;
      name: string;
      size: number;
      modified: number;
      mimeType: string;
    }) => ({
      id: file.path,
      path: file.path,
      name: file.name,
      isFolder: false,
      size: file.size,
      mimeType: file.mimeType,
      modifiedAt: new Date(file.modified * 1000),
    }));

    const directories: UnifiedDirectory[] = (responseData?.directories ?? []).map((dir: {
      path: string;
      name: string;
    }) => ({
      id: dir.path,
      path: dir.path,
      name: dir.name,
      isFolder: true,
    }));

    return {
      files,
      directories,
      path: responseData?.path ?? path,
      redirected: responseData?.redirected ?? false,
    };
  }

  getParentPath(currentPath: string): string {
    const segments = currentPath.split('/');
    if (segments.length > 2) segments.pop();
    return segments.join('/');
  }

  isRootPath(path: string): boolean {
    return path === this.rootPath || path === 'public/files';
  }

  openFile(file: UnifiedFile): void {
    // Transform storage path to public URL
    const url = file.path.replace('public/', '/uploads/');
    window.open(url, '_blank');
  }
}
