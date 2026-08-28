import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import { useToasts } from '@/Composables/useToasts';
import type { ApiResponse } from '@/Types/api.d';

export interface FileEntry {
  path: string;
  name: string;
  type: 'file';
  size: number;
  modified: number;
  mimeType: string;
  /** Parent directory — only set on recursive search results. */
  directory?: string;
}

export interface DirectoryEntry {
  path: string;
  name: string;
  type: 'directory';
}

interface ListingPayload {
  files: FileEntry[];
  directories: DirectoryEntry[];
  path: string;
  redirected?: boolean;
}

async function getJson<T>(url: string): Promise<{ data: T | null; meta: Record<string, unknown> }> {
  const response = await fetch(url, {
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
  });

  const body = await response.json().catch(() => null) as ApiResponse<T> | null;

  if (!response.ok || !body?.success) {
    throw new Error((body && 'message' in body && body.message) || `Request failed (${response.status})`);
  }

  return { data: body.data, meta: body.meta ?? {} };
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
  const error = ref<string | null>(null);

  const toasts = useToasts();

  const searchResults = ref<FileEntry[] | null>(null);
  const searching = ref<boolean>(false);
  const searchTruncated = ref<boolean>(false);

  async function fetchListing(path: string) {
    loading.value = true;
    error.value = null;

    try {
      const query: Record<string, string> = { path };
      if (extensions?.length) {
        query.extensions = extensions.join(',');
      }

      const { data } = await getJson<ListingPayload>(route('api.v1.admin.files.index', query));

      filesRaw.value = data?.files ?? [];
      directoriesRaw.value = data?.directories ?? [];
      currentPath.value = data?.path ?? path;

      if (data?.redirected) {
        router.reload({ only: ['flash'] });
      }
    }
    catch (e: unknown) {
      // Say so. Blanking the lists without a message renders as "this folder is empty",
      // which is indistinguishable from a permissions problem or a dropped connection.
      error.value = e instanceof Error ? e.message : 'Failed to load files';
      toasts.error(error.value);
      filesRaw.value = [];
      directoriesRaw.value = [];
    }
    finally {
      // finally, not a trailing assignment: a throw here used to pin FileSelector's
      // full-panel overlay spinner up for the rest of the session.
      loading.value = false;
    }
  }

  async function search(query: string) {
    if (query.trim().length < 2) {
      searchResults.value = null;
      searchTruncated.value = false;
      return;
    }

    searching.value = true;

    try {
      const params: Record<string, string> = { q: query.trim(), path: currentPath.value };
      if (extensions?.length) {
        params.extensions = extensions.join(',');
      }

      const { data, meta } = await getJson<{ files: FileEntry[] }>(
        route('api.v1.admin.files.search', params),
      );

      searchResults.value = data?.files ?? [];
      searchTruncated.value = meta.truncated === true;
    }
    catch (e: unknown) {
      toasts.error(e instanceof Error ? e.message : 'Search failed');
      searchResults.value = [];
      searchTruncated.value = false;
    }
    finally {
      searching.value = false;
    }
  }

  function clearSearch() {
    searchResults.value = null;
    searchTruncated.value = false;
  }

  async function back() {
    const segments = currentPath.value.split('/');
    if (segments.length > 2) segments.pop();
    const parent = segments.join('/');
    await fetchListing(parent);
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
