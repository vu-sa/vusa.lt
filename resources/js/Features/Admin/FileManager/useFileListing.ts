import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import type { DirectoryEntry, FileEntry, ListingPayload } from './types';
import { getFilesJson } from './fileApi';
import { useFileSearch } from './useFileSearch';

import { useToasts } from '@/Composables/useToasts';

export type { DirectoryEntry, FileEntry } from './types';

/**
 * Self-fetching listing for the file picker dialog, where a page visit would navigate the page
 * behind it. `extensions` filters server-side; the admin page keeps its listing in Inertia props.
 */
export function useFileListing(initialPath = 'public/files', extensions?: string[]) {
  const toasts = useToasts();

  const filesRaw = ref<FileEntry[]>([]);
  const directoriesRaw = ref<DirectoryEntry[]>([]);
  const currentPath = ref<string>(initialPath);
  const loading = ref<boolean>(false);
  const error = ref<string | null>(null);

  const {
    results: searchResults,
    searching,
    truncated: searchTruncated,
    search: runSearch,
    clear: clearSearch,
  } = useFileSearch(extensions);

  let latestRequest = 0;

  async function fetchListing(path: string) {
    const request = ++latestRequest;
    loading.value = true;
    error.value = null;

    try {
      const query: Record<string, string> = { path };
      if (extensions?.length) {
        query.extensions = extensions.join(',');
      }

      const { data } = await getFilesJson<ListingPayload>(route('api.v1.admin.files.index', query));

      if (request !== latestRequest) return;

      filesRaw.value = data?.files ?? [];
      directoriesRaw.value = data?.directories ?? [];
      currentPath.value = data?.path ?? path;

      if (data?.redirected) {
        router.reload({ only: ['flash'] });
      }
    }
    catch (e: unknown) {
      if (request !== latestRequest) return;
      // Keep a failed listing distinct from an empty folder.
      error.value = e instanceof Error ? e.message : 'Failed to load files';
      toasts.error(error.value);
      filesRaw.value = [];
      directoriesRaw.value = [];
    }
    finally {
      // In finally: a throw once pinned FileSelector's overlay spinner for the session.
      if (request === latestRequest) loading.value = false;
    }
  }

  function search(query: string) {
    return runSearch(query, currentPath.value);
  }

  async function back() {
    const segments = currentPath.value.split('/');
    if (segments.length > 2) segments.pop();
    await fetchListing(segments.join('/'));
  }

  // Initial fetch
  fetchListing(initialPath);

  return {
    filesRaw,
    directoriesRaw,
    currentPath,
    loading,
    error,
    searchResults,
    searching,
    searchTruncated,
    fetch: fetchListing,
    search,
    clearSearch,
    back,
  };
}
