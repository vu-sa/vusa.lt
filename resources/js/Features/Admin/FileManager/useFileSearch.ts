import { ref } from 'vue';

import type { FileEntry } from './types';
import { getFilesJson } from './fileApi';

import { useToasts } from '@/Composables/useToasts';

/**
 * Recursive filename search beneath a directory.
 *
 * Split out of useFileListing because the two entry points need it on different terms: the
 * admin page keeps its listing in Inertia props (so the URL tracks the folder), while the
 * Tiptap dialog fetches its own. Search is identical for both.
 */
export function useFileSearch(extensions?: string[]) {
  const toasts = useToasts();

  const results = ref<FileEntry[] | null>(null);
  const searching = ref(false);
  const truncated = ref(false);

  async function search(query: string, path: string) {
    if (query.trim().length < 2) {
      clear();
      return;
    }

    searching.value = true;

    try {
      const params: Record<string, string> = { q: query.trim(), path };
      if (extensions?.length) {
        params.extensions = extensions.join(',');
      }

      const { data, meta } = await getFilesJson<{ files: FileEntry[] }>(
        route('api.v1.admin.files.search', params),
      );

      results.value = data?.files ?? [];
      truncated.value = meta.truncated === true;
    }
    catch (e: unknown) {
      toasts.error(e instanceof Error ? e.message : 'Search failed');
      results.value = [];
      truncated.value = false;
    }
    finally {
      searching.value = false;
    }
  }

  function clear() {
    results.value = null;
    truncated.value = false;
  }

  return { results, searching, truncated, search, clear };
}
