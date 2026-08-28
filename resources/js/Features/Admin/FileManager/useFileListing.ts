import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import type { DirectoryEntry, FileEntry, ListingPayload } from './types';
import { getFilesJson } from './fileApi';
import { useFileSearch } from './useFileSearch';

import { useToasts } from '@/Composables/useToasts';

export type { DirectoryEntry, FileEntry } from './types';

/**
 * Self-fetching listing for callers with no Inertia props of their own — i.e. the file picker
 * inside a dialog, where a real page visit would be wrong (it would push browser history and
 * navigate the page behind the modal).
 *
 * The admin page does not use this: it keeps its listing in Inertia props so the URL tracks
 * the open folder and the browser's Back button walks back up the tree.
 *
 * @param extensions Restricts the listing to files with these extensions (e.g. an image
 *                    picker asking for only jpg/png/webp/...). Filtering happens server-side
 *                    so unrelated files (PDFs, docs, ...) never reach the browser in the
 *                    first place — matches the click-time guard in FileManager.vue, which
 *                    stays as a backstop for callers that don't pass this through.
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

  async function fetchListing(path: string) {
    loading.value = true;
    error.value = null;

    try {
      const query: Record<string, string> = { path };
      if (extensions?.length) {
        query.extensions = extensions.join(',');
      }

      const { data } = await getFilesJson<ListingPayload>(route('api.v1.admin.files.index', query));

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
