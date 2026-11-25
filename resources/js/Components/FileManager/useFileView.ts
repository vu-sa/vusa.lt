import { computed, ref, watch, type Ref } from 'vue';
import { useFuse } from '@vueuse/integrations/useFuse';

import { getFileKind } from '@/Utils/fileIcons';

import type { ActiveView, DirectoryEntry, FileEntry, FileManagerViewState, SortDir, SortKey, TypeFilter } from './types';
import { matchesTypeFilter, TYPE_FILTER_KINDS } from './utils';

export const FILE_PAGE_SIZE = 50;

/**
 * What the file manager shows of a folder: fuzzy search, the type filter, sort, the starred and
 * recent views, recursive search results and "show more" paging.
 */
export function useFileView(options: {
  files: () => FileEntry[];
  directories: () => DirectoryEntry[];
  searchResults: () => FileEntry[] | null | undefined;
  starredEntries: Ref<FileEntry[]>;
  initialState: Partial<FileManagerViewState>;
}) {
  const [initialSortKey, initialSortDir] = (options.initialState.sort ?? 'name:asc').split(':') as [SortKey, SortDir];

  const search = ref(options.initialState.q ?? '');
  const searchEverywhere = ref(false);
  const typeFilter = ref<TypeFilter>(options.initialState.type ?? 'all');
  const sortKey = ref<SortKey>(initialSortKey);
  const sortDir = ref<SortDir>(initialSortDir);
  const activeView = ref<ActiveView>('browse');
  const visibleCount = ref(FILE_PAGE_SIZE);

  // A stale `?type=` from an old link falls back to all.
  if (typeFilter.value !== 'all' && typeFilter.value !== 'folder' && !(TYPE_FILTER_KINDS as readonly string[]).includes(typeFilter.value)) {
    typeFilter.value = 'all';
  }

  const fuseOptions = { fuseOptions: { keys: ['name'], threshold: 0.4 }, matchAllWhenSearchEmpty: true };
  const { results: fileSearchResults } = useFuse(search, options.files, fuseOptions);
  const { results: directorySearchResults } = useFuse(search, options.directories, fuseOptions);

  const isRecursiveSearch = computed(() => searchEverywhere.value && search.value.trim().length >= 2);

  const shownDirectories = computed<DirectoryEntry[]>(() => {
    if (isRecursiveSearch.value || activeView.value !== 'browse') return [];
    return search.value === '' ? options.directories() : directorySearchResults.value.map(result => result.item);
  });

  const displayedDirectories = computed(() => (typeFilter.value === 'all' || typeFilter.value === 'folder' ? shownDirectories.value : []));

  /** Files before the type filter, which the filter menu counts. */
  const baseFiles = computed<FileEntry[]>(() => {
    if (activeView.value === 'starred') {
      const needle = search.value.trim().toLowerCase();
      return options.starredEntries.value.filter(entry => !needle || entry.name.toLowerCase().includes(needle));
    }
    if (isRecursiveSearch.value) return options.searchResults() ?? [];
    return search.value === '' ? options.files() : fileSearchResults.value.map(result => result.item);
  });

  const typeCounts = computed(() => {
    const counts: Partial<Record<TypeFilter, number>> = {};
    for (const file of baseFiles.value) {
      const kind = getFileKind(file.name) as TypeFilter;
      counts[kind] = (counts[kind] ?? 0) + 1;
    }
    return counts;
  });

  const displayedFiles = computed<FileEntry[]>(() => {
    if (typeFilter.value === 'folder') return [];

    const list = baseFiles.value.filter(file => matchesTypeFilter(file, typeFilter.value));
    const key = activeView.value === 'recent' ? 'modified' : sortKey.value;
    const dir = activeView.value === 'recent' ? -1 : sortDir.value === 'asc' ? 1 : -1;

    return [...list].sort((a, b) => {
      if (key === 'name') return a.name.localeCompare(b.name, 'lt', { numeric: true }) * dir;
      if (key === 'size') return ((a.size || 0) - (b.size || 0)) * dir;
      return ((a.modified || 0) - (b.modified || 0)) * dir;
    });
  });

  const visibleFiles = computed(() => displayedFiles.value.slice(0, visibleCount.value));
  const isFiltered = computed(() => search.value.trim() !== '' || typeFilter.value !== 'all');

  watch([search, searchEverywhere, typeFilter, sortKey, sortDir, activeView], () => {
    visibleCount.value = FILE_PAGE_SIZE;
  });

  function setSort(key: SortKey, dir: SortDir): void {
    sortKey.value = key;
    sortDir.value = dir;
  }

  function clearFilters(): void {
    search.value = '';
    typeFilter.value = 'all';
  }

  return {
    search,
    searchEverywhere,
    typeFilter,
    sortKey,
    sortDir,
    activeView,
    visibleCount,
    isRecursiveSearch,
    shownDirectories,
    displayedDirectories,
    baseFiles,
    typeCounts,
    displayedFiles,
    visibleFiles,
    isFiltered,
    setSort,
    clearFilters,
  };
}
