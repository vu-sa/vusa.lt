import { ref } from 'vue';
import { useFetch } from '@vueuse/core';
import { router } from '@inertiajs/vue3';

export interface FileEntry {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType: string;
}

export interface DirectoryEntry {
  path: string;
  name: string;
  type: 'directory';
}

/**
 * @param extensions Restricts the listing to files with these extensions (e.g. an image
 *                    picker asking for only jpg/png/webp/...). Filtering happens server-side
 *                    so unrelated files (PDFs, docs, ...) never reach the browser in the
 *                    first place — matches the click-time guard in FileManager.vue, which
 *                    stays as a backstop for callers that don't pass this through.
 */
export function useFileListing(initialPath = 'public/files', extensions?: string[]) {
  const filesRaw = ref<FileEntry[]>([]);
  const directoriesRaw = ref<DirectoryEntry[]>([]);
  const currentPath = ref<string>(initialPath);
  const loading = ref<boolean>(false);

  async function fetch(path: string) {
    loading.value = true;
    const query: Record<string, string> = { path };
    if (extensions?.length) {
      query.extensions = extensions.join(',');
    }
    const { data } = await useFetch(route('api.v1.admin.files.index', query)).get().json();

    // Handle standardized API response format
    const responseData = data.value?.success ? data.value.data : data.value;
    filesRaw.value = (responseData?.files as FileEntry[]) ?? [];
    directoriesRaw.value = (responseData?.directories as DirectoryEntry[]) ?? [];
    currentPath.value = (responseData?.path as string) ?? path;

    if (responseData?.redirected) {
      router.reload({ only: ['flash'] });
    }

    loading.value = false;
  }

  async function back() {
    const segments = currentPath.value.split('/');
    if (segments.length > 2) segments.pop();
    const parent = segments.join('/');
    await fetch(parent);
  }

  // Initial fetch
  fetch(initialPath);

  return {
    filesRaw,
    directoriesRaw,
    currentPath,
    loading,
    fetch,
    back,
  };
}
