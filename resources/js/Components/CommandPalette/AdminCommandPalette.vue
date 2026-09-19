<template>
  <PaletteDialog v-model:open="isOpen" :title="$t('Komandų paletė')"
    :description="$t('Ieškokite veiksmų, posėdžių ir darbotvarkės punktų')">
    <!-- Custom search input (not CommandInput to avoid internal filtering) -->
    <div class="flex h-12 items-center gap-2 border-b border-border px-3 pr-12">
      <div class="relative flex size-4 items-center justify-center">
        <Search v-if="!isSearching" class="size-4 shrink-0 text-muted-foreground" />
        <div v-else class="size-4 animate-spin border-2 border-border border-t-foreground" />
      </div>
      <input ref="searchInputRef" v-model="query" type="text" :placeholder="$t('shell.chrome.search_field')"
        class="h-12 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground sm:text-sm"
        @keydown.escape="close"
        @keydown.down.prevent="focusFirstResult"
        @keydown.enter="query.trim() && goToUnifiedSearch()">
    </div>

    <CommandList class="max-h-[50vh] scroll-py-2 max-sm:max-h-none max-sm:flex-1 sm:max-h-[60vh]">
      <!-- Loading skeleton -->
      <div v-if="isSearching && query" class="space-y-1 p-2">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-3 py-3">
          <div class="size-8 animate-pulse bg-secondary" />
          <div class="flex-1 space-y-2">
            <div class="h-4 w-3/4 animate-pulse bg-secondary" />
            <div class="h-3 w-1/2 animate-pulse bg-secondary/70" />
          </div>
        </div>
      </div>

      <template v-else>
        <!-- Search everything on the unified search page -->
        <CommandGroup v-if="query.trim()" class="px-2">
          <PaletteRow
            value="search-everywhere"
            :icon="Search"
            :title="`${$t('Ieškoti visur')} „${query.trim()}“`"
            @select="goToUnifiedSearch"
          />
        </CommandGroup>

        <!-- Pinned pages, then recents: both empty-state only (O20, O15) -->
        <CommandGroup v-if="!query && pinnedItems.length > 0" :heading="$t('shell.palette.pinned')" class="px-2">
          <PaletteRow
            v-for="item in pinnedItems" :key="`pinned-${item.id}`"
            :value="`pinned-${item.id}`"
            :icon="resolvePageIcon(item.routeName, item.href)"
            :title="item.title"
            @select="handleRecentSelect(item)"
          >
            <template #trailing>
              <PinButton pinned @toggle="togglePin({ routeName: item.routeName, href: item.href, title: item.title })" />
            </template>
          </PaletteRow>
        </CommandGroup>

        <CommandGroup v-if="!query && topRecentItems.length > 0" :heading="$t('shell.palette.recent')" class="px-2">
          <PaletteRow
            v-for="item in topRecentItems" :key="`recent-${item.type}-${item.id}`"
            :value="`recent-${item.type}-${item.id}`"
            :icon="resolvePageIcon(item.routeName, item.href)"
            :title="item.title"
            :subtitle="getRecentTypeBadge(item.type)"
            @select="handleRecentSelect(item)"
          >
            <template v-if="item.type === 'page' && item.routeName" #trailing>
              <PinButton
                :pinned="isPinned({ routeName: item.routeName, href: item.href })"
                @toggle="togglePin({ routeName: item.routeName, href: item.href, title: item.title })"
              />
            </template>
          </PaletteRow>
        </CommandGroup>

        <!-- Create -->
        <CommandGroup v-if="createActions.length > 0" :heading="$t('shell.palette.create')" class="px-2">
          <ActionResult v-for="action in createActions" :key="action.id" :action />
        </CommandGroup>

        <!-- Go to: the current workspace first -->
        <CommandGroup v-if="goToActions.length > 0" :heading="$t('shell.palette.go_to')" class="px-2">
          <ActionResult v-for="action in goToActions" :key="action.id" :action />
        </CommandGroup>

        <!-- Flat interleaved search results -->
        <CommandGroup v-if="flatHits.length > 0" class="px-2">
          <CommandItem
            v-for="hit in flatHits"
            :key="hit.id"
            :value="hit.id"
            class="group cursor-pointer px-3 py-2.5 data-[highlighted]:bg-secondary"
            @select="handleHitSelect(hit)"
          >
            <SearchHitRow
              :hit
              show-actions
              @view="navigateToHref(hit.viewHref)"
              @edit="navigateToHref(hit.editHref)"
            />
          </CommandItem>
        </CommandGroup>

        <!-- Rate limit warning -->
        <div v-if="searchError && searchError.includes('užklausų')" class="mx-4 my-2 border border-status-attention-border bg-status-attention-surface p-3">
          <p class="flex items-center gap-2 text-sm text-status-attention">
            <Clock class="size-4" />
            {{ searchError }}
          </p>
        </div>

        <!-- Empty state -->
        <div v-if="query && !hasResults && !isSearching && !searchError" class="py-14 text-center">
          <div class="mx-auto mb-4 flex size-12 items-center justify-center border border-border bg-secondary">
            <SearchX class="size-6 text-muted-foreground" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('Rezultatų nerasta') }}
          </p>
          <p class="mt-1 text-xs text-muted-foreground">
            {{ $t('Pabandykite kitą paieškos frazę') }}
          </p>
        </div>
      </template>
    </CommandList>

    <!-- Footer with keyboard hints -->
    <div class="hidden items-center gap-5 border-t border-border bg-secondary/50 px-4 py-2.5 text-xs text-muted-foreground sm:flex">
      <span class="flex items-center gap-1.5">
        <span class="flex gap-0.5">
          <kbd class="inline-flex size-5 items-center justify-center border border-border bg-background"><ArrowUp class="size-3" /></kbd>
          <kbd class="inline-flex size-5 items-center justify-center border border-border bg-background"><ArrowDown class="size-3" /></kbd>
        </span>
        {{ $t('naršyti') }}
      </span>
      <span class="flex items-center gap-1.5">
        <kbd class="inline-flex h-5 items-center justify-center border border-border bg-background px-1.5 font-mono">↵</kbd>
        {{ $t('pasirinkti') }}
      </span>
      <span class="flex items-center gap-1.5">
        <kbd class="inline-flex h-5 items-center justify-center border border-border bg-background px-1.5 font-mono">esc</kbd>
        {{ $t('uždaryti') }}
      </span>
    </div>
  </PaletteDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn } from '@vueuse/core';
import {
  Search,
  Clock,
  ArrowUp,
  ArrowDown,
  SearchX,
} from 'lucide-vue-next';

import PaletteDialog from './PaletteDialog.vue';
import { useCommandActions } from './useCommandActions';
import ActionResult from './results/ActionResult.vue';
import PaletteRow from './results/PaletteRow.vue';
import PinButton from './results/PinButton.vue';

import { useAdminSearch, type MultiSearchResults } from '@/Composables/useAdminSearch';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import { resolvePageIcon } from '@/Composables/adminPageCatalog';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import {
  collectAllTabHits,
  type MapperContext,
  type NormalizedSearchHit,
  type SearchCollectionKey,
} from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import SearchHitRow from '@/Features/Admin/AdminSearch/Components/SearchHitRow.vue';
import {
  CommandList,
  CommandGroup,
  CommandItem,
} from '@/Components/ui/command';

/** Search collection → the catalog `entityType` whose section owns it, for workspace ranking. */
const collectionEntityType: Record<SearchCollectionKey, string> = {
  meetings: 'meeting',
  agendaItems: 'agenda_item',
  institutions: 'institution',
  resources: 'resource',
  duties: 'duty',
  documents: 'document',
  news: 'news',
  pages: 'page',
  calendar: 'calendar',
  users: 'user',
};

// Command palette state
const { isOpen, query, recentItems, close } = useCommandPalette();

const { isPinned, togglePin, pinnedPages } = useUIPreferences();

// Admin search
const { multiSearch, initialize: initializeSearch, isRateLimited, getDirectInstitutionIds } = useAdminSearch();

// Command actions
const { filterActions, workspaceKeyForEntity, activeWorkspace } = useCommandActions();

// Local state
const isSearching = ref(false);
const searchError = ref<string | null>(null);
const searchResults = ref<MultiSearchResults>(createEmptyMultiSearchResults());
const searchInputRef = ref<HTMLInputElement | null>(null);

const pinnedItems = computed<RecentItem[]>(() => pinnedPages.value);

// Top 5 recents, minus anything already listed as pinned
const topRecentItems = computed<RecentItem[]>(() => {
  const pinnedHrefs = new Set(pinnedItems.value.map(item => item.href));

  return recentItems.value.filter(item => !pinnedHrefs.has(item.href)).slice(0, 5);
});

// Context for mappers that need user-relative state (isRelated badges).
const mapperCtx = computed<MapperContext>(() => ({
  directInstitutionIds: [
    ...getDirectInstitutionIds('meetings'),
    ...getDirectInstitutionIds('agenda_items'),
  ],
}));

// Flat, interleaved hits (relevance-sorted when a query is present); hits belonging to the
// workspace the user is standing in come first, keeping relevance order within each group.
const flatHits = computed<NormalizedSearchHit[]>(() => {
  const hits = collectAllTabHits(searchResults.value, { query: query.value, dutyCtx: mapperCtx.value });
  const current = activeWorkspace.value?.key;

  return [...hits].sort((a, b) => {
    const inCurrent = (hit: NormalizedSearchHit) => Number(workspaceKeyForEntity(collectionEntityType[hit.collection]) === current);

    return inCurrent(b) - inCurrent(a);
  });
});

// The catalog already gates every entry by permission, so there is nothing left to filter here.
const matchingActions = computed(() => filterActions(query.value));
const createActions = computed(() => matchingActions.value.filter(action => action.category === 'create').slice(0, query.value ? 5 : 6));
const goToActions = computed(() => {
  const navigation = matchingActions.value.filter(action => action.category === 'navigation');

  // With nothing typed, stay useful without being a wall: only the current workspace's pages.
  return (query.value ? navigation : navigation.filter(action => action.workspaceKey === activeWorkspace.value?.key)).slice(0, 8);
});

// Check if we have any results
const hasResults = computed(() => (
  createActions.value.length > 0
  || goToActions.value.length > 0
  || flatHits.value.length > 0
));

// Debounced search function - 300ms debounce to reduce request frequency
const performSearch = useDebounceFn(async (searchQuery: string) => {
  if (!searchQuery.trim()) {
    searchResults.value = createEmptyMultiSearchResults();
    isSearching.value = false;
    return;
  }

  // Skip if rate limited
  if (isRateLimited.value) {
    searchError.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.');
    isSearching.value = false;
    return;
  }

  isSearching.value = true;
  searchError.value = null;

  try {
    searchResults.value = await multiSearch(searchQuery, {
      meetingsLimit: 3,
      agendaItemsLimit: 3,
      newsLimit: 3,
      pagesLimit: 3,
      calendarLimit: 3,
      institutionsLimit: 3,
      documentsLimit: 3,
    });
  }
  catch (error) {
    const message = error instanceof Error ? error.message : 'Search failed';
    // Show user-friendly message for rate limiting
    if (message.includes('Too many requests')) {
      searchError.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.');
    }
    else {
      searchError.value = message;
    }
    // Don't clear results on error - keep previous results visible
  }
  finally {
    isSearching.value = false;
  }
}, 300);

// Watch query changes
watch(query, (newQuery) => {
  if (newQuery.trim()) {
    performSearch(newQuery);
  }
  else {
    searchResults.value = createEmptyMultiSearchResults();
  }
});

// Track if search has been initialized to prevent duplicate calls
let searchInitialized = false;

// Initialize search and focus input when opened
watch(isOpen, (opened) => {
  if (opened) {
    // Only initialize once per session
    if (!searchInitialized) {
      initializeSearch();
      searchInitialized = true;
    }
    // Focus the search input after dialog opens with fallback for animation delay
    nextTick(() => {
      searchInputRef.value?.focus();
      // Fallback in case dialog animation delays rendering
      setTimeout(() => {
        searchInputRef.value?.focus();
      }, 50);
    });
  }
});

// Focus the first result when pressing down arrow in the input
const focusFirstResult = () => {
  const firstItem = document.querySelector('[data-slot="command"] [role="option"]') as HTMLElement;
  if (firstItem) {
    firstItem.focus();
  }
};

// Navigate to the unified search page with the current query (All tab)
const goToUnifiedSearch = () => {
  const trimmed = query.value.trim();
  close();
  router.visit(route('search.index', trimmed ? { q: trimmed } : {}));
};

// Handle a flat search hit selection
const handleHitSelect = (hit: NormalizedSearchHit) => {
  if (hit.href) {
    close();
    router.visit(hit.href);
  }
};

// View/edit quick-action buttons on a hit row
const navigateToHref = (href?: string) => {
  if (href) {
    close();
    router.visit(href);
  }
};

// Handle a pinned or recent item selection
const handleRecentSelect = (item: RecentItem) => {
  close();

  if (item.href) {
    router.visit(item.href);
  }
};

// Get badge text for recent item type
const getRecentTypeBadge = (type: RecentItem['type']): string => {
  switch (type) {
    case 'meeting':
      return $t('Posėdis');
    case 'agenda_item':
      return $t('Darbotvarkės punktas');
    case 'action':
      return $t('Veiksmas');
    case 'news':
      return $t('Naujiena');
    case 'page':
      return $t('Puslapis');
    case 'calendar':
      return $t('Renginys');
    case 'institution':
      return $t('Institucija');
    case 'document':
      return $t('Dokumentas');
    default:
      return '';
  }
};
</script>
