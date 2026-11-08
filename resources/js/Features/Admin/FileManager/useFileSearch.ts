import { ref } from 'vue';

import type { FileEntry } from './types';
import { getFilesJson } from './fileApi';

import { useToasts } from '@/Composables/useToasts';

// Shared by the props-backed admin page and the self-fetching file picker.
export function useFileSearch(extensions?: string[]) {
  const toasts = useToasts();

  const results = ref<FileEntry[] | null>(null);
  const searching = ref(false);
  const truncated = ref(false);

  let latestRequest = 0;

  async function search(query: string, path: string) {
    if (query.trim().length < 2) {
      clear();
      return;
    }

    const request = ++latestRequest;
    searching.value = true;

    try {
      const params: Record<string, string> = { q: query.trim(), path };
      if (extensions?.length) {
        params.extensions = extensions.join(',');
      }

      const { data, meta } = await getFilesJson<{ files: FileEntry[] }>(
        route('api.v1.admin.files.search', params),
      );

      if (request !== latestRequest) return;

      results.value = data?.files ?? [];
      truncated.value = meta.truncated === true;
    }
    catch (e: unknown) {
      if (request !== latestRequest) return;
      toasts.error(e instanceof Error ? e.message : 'Search failed');
      results.value = [];
      truncated.value = false;
    }
    finally {
      if (request === latestRequest) searching.value = false;
    }
  }

  function clear() {
    latestRequest += 1;
    searching.value = false;
    results.value = null;
    truncated.value = false;
  }

  return { results, searching, truncated, search, clear };
}
