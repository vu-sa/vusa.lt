<template>
  <CommandDialog v-model:open="isOpen" :title="$t('Komandų paletė')"
    :description="$t('Ieškokite veiksmų, posėdžių ir darbotvarkės punktų')">
    <!-- Custom search input (not CommandInput to avoid internal filtering) -->
    <div class="flex h-12 items-center gap-2 border-b px-3">
      <div class="relative flex items-center justify-center size-4">
        <Search v-if="!isSearching" class="size-4 shrink-0 text-muted-foreground/50" />
        <div v-else class="size-4 rounded-full border-2 border-primary/30 border-t-primary animate-spin" />
      </div>
      <input ref="searchInputRef" v-model="query" type="text" :placeholder="$t('Ieškoti veiksmų, posėdžių...')"
        class="flex-1 h-12 bg-transparent text-base outline-none placeholder:text-muted-foreground/50 sm:text-sm"
        @keydown.escape="close"
        @keydown.down.prevent="focusFirstResult"
        @keydown.enter="query.trim() && goToUnifiedSearch()">
    </div>

    <CommandList class="max-h-[50vh] sm:max-h-[60vh] scroll-py-2">
      <!-- Loading skeleton -->
      <div v-if="isSearching && query" class="p-2 space-y-1">
        <div v-for="i in 3" :key="i" class="flex items-center gap-3 px-3 py-3 rounded-lg">
          <div class="size-9 rounded-lg bg-muted/50 animate-pulse" />
          <div class="flex-1 space-y-2">
            <div class="h-4 w-3/4 rounded bg-muted/50 animate-pulse" />
            <div class="h-3 w-1/2 rounded bg-muted/30 animate-pulse" />
          </div>
        </div>
      </div>

      <template v-else>
        <!-- Search everything on the unified search page -->
        <CommandGroup v-if="query.trim()" class="px-2">
          <CommandItem
            value="search-everywhere"
            class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
            @select="goToUnifiedSearch"
          >
            <div class="flex w-full items-center gap-3">
              <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary transition-colors group-hover:bg-primary/15">
                <Search class="size-4" />
              </div>
              <span class="flex-1 truncate text-sm font-medium">
                {{ $t('Ieškoti visur') }} „{{ query.trim() }}“
              </span>
              <ChevronRight class="size-4 shrink-0 text-muted-foreground/50 opacity-0 transition-opacity group-hover:opacity-100" />
            </div>
          </CommandItem>
        </CommandGroup>

        <!-- Recent items (when query is empty) -->
        <CommandGroup v-if="!query && topRecentItems.length > 0" :heading="$t('Neseniai')" class="px-2">
          <CommandItem v-for="item in topRecentItems" :key="`recent-${item.type}-${item.id}`"
            :value="`recent-${item.type}-${item.id}`"
            class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent"
            @select="handleRecentSelect(item)">
            <div class="flex items-center gap-3 w-full">
              <div
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-muted/50 text-muted-foreground group-hover:bg-background group-hover:shadow-sm transition-all">
                <component :is="resolveRecentIcon(item)" class="size-4" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="block font-medium truncate text-sm">{{ item.title }}</span>
                <span class="text-xs text-muted-foreground">
                  {{ getRecentTypeBadge(item.type) }}
                </span>
              </div>
              <button
                v-if="item.type === 'page' && item.routeName"
                type="button"
                class="shrink-0 p-1 rounded-md text-muted-foreground hover:text-foreground hover:bg-background transition-colors"
                :class="isPinned({ routeName: item.routeName, href: item.href })
                  ? 'text-amber-500 hover:text-amber-500 opacity-100'
                  : 'opacity-0 group-hover:opacity-100'"
                :title="isPinned({ routeName: item.routeName, href: item.href }) ? $t('Atsegti') : $t('Prisegti puslapį')"
                :aria-label="isPinned({ routeName: item.routeName, href: item.href }) ? $t('Atsegti') : $t('Prisegti puslapį')"
                @click.stop="togglePin({ routeName: item.routeName, href: item.href, title: item.title })"
              >
                <Star class="size-4" :fill="isPinned({ routeName: item.routeName, href: item.href }) ? 'currentColor' : 'none'" />
              </button>
              <ChevronRight
                class="size-4 text-muted-foreground/50 opacity-0 group-hover:opacity-100 transition-opacity" />
            </div>
          </CommandItem>
        </CommandGroup>

        <!-- Quick Actions -->
        <CommandGroup v-if="filteredActions.length > 0" :heading="$t('Veiksmai')" class="px-2">
          <ActionResult v-for="action in filteredActions" :key="action.id" :action />
        </CommandGroup>

        <!-- Flat interleaved search results -->
        <CommandGroup v-if="flatHits.length > 0" class="px-2">
          <CommandItem
            v-for="hit in flatHits"
            :key="hit.id"
            :value="hit.id"
            class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
            @select="handleHitSelect(hit)"
          >
            <SearchHitRow :hit="hit" />
          </CommandItem>
        </CommandGroup>

        <!-- Rate limit warning -->
        <div v-if="searchError && searchError.includes('užklausų')" class="mx-4 my-2 p-3 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
          <p class="text-sm text-amber-700 dark:text-amber-400 flex items-center gap-2">
            <Clock class="size-4" />
            {{ searchError }}
          </p>
        </div>

        <!-- Empty state -->
        <div v-if="query && !hasResults && !isSearching && !searchError" class="py-14 text-center">
          <div class="mx-auto w-12 h-12 rounded-full bg-muted/50 flex items-center justify-center mb-4">
            <SearchX class="size-6 text-muted-foreground/50" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('Rezultatų nerasta') }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            {{ $t('Pabandykite kitą paieškos frazę') }}
          </p>
        </div>

        <!-- Initial state hint -->
        <div v-if="!query && recentItems.length === 0" class="py-10 text-center">
          <div class="mx-auto w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center mb-4">
            <Sparkles class="size-5 text-primary" />
          </div>
          <p class="text-sm font-medium text-foreground">
            {{ $t('Pradėkite rašyti') }}
          </p>
          <p class="text-xs text-muted-foreground mt-1">
            {{ $t('arba naršykite veiksmus žemiau') }}
          </p>
        </div>
      </template>
    </CommandList>

    <!-- Footer with keyboard hints -->
    <div class="flex items-center justify-between border-t bg-muted/30 px-3 sm:px-4 py-2 sm:py-2.5">
      <div class="hidden sm:flex items-center gap-5 text-xs text-muted-foreground">
        <span class="flex items-center gap-1.5">
          <span class="flex gap-0.5">
            <kbd
              class="inline-flex size-5 items-center justify-center rounded border bg-background font-mono text-[10px] shadow-sm">
              <ArrowUp class="size-3" />
            </kbd>
            <kbd
              class="inline-flex size-5 items-center justify-center rounded border bg-background font-mono text-[10px] shadow-sm">
              <ArrowDown class="size-3" />
            </kbd>
          </span>
          <span class="text-muted-foreground/70">{{ $t('naršyti') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd
            class="inline-flex h-5 items-center justify-center rounded border bg-background px-1.5 font-mono text-[10px] shadow-sm">
            ↵
          </kbd>
          <span class="text-muted-foreground/70">{{ $t('pasirinkti') }}</span>
        </span>
        <span class="flex items-center gap-1.5">
          <kbd
            class="inline-flex h-5 items-center justify-center rounded border bg-background px-1.5 font-mono text-[10px] shadow-sm">
            esc
          </kbd>
          <span class="text-muted-foreground/70">{{ $t('uždaryti') }}</span>
        </span>
      </div>
      <div class="sm:hidden text-xs text-muted-foreground">
        {{ $t('Paspauskite, kad pasirinktumėte') }}
      </div>
      <div class="text-[10px] text-muted-foreground/50 font-medium tracking-wide uppercase">
        VU SA
      </div>
    </div>
  </CommandDialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, type Component } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn } from '@vueuse/core';
import {
  Search,
  Clock,
  ArrowUp,
  ArrowDown,
  ChevronRight,
  SearchX,
  Sparkles,
  Star,
} from 'lucide-vue-next';

import { useCommandActions } from './useCommandActions';
import ActionResult from './results/ActionResult.vue';

import { useAdminSearch, type MultiSearchResults } from '@/Composables/useAdminSearch';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import { resolvePageIcon } from '@/Composables/adminPageCatalog';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import { useAvailableQuickActions } from '@/Composables/useQuickActions';
import {
  collectAllTabHits,
  type MapperContext,
  type NormalizedSearchHit,
} from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import SearchHitRow from '@/Features/Admin/AdminSearch/Components/SearchHitRow.vue';
import {
  CommandDialog,
  CommandList,
  CommandGroup,
  CommandItem,
} from '@/Components/ui/command';

// Command palette state
const { isOpen, query, recentItems, close } = useCommandPalette();

// UI preferences (for quick-action visibility in the palette)
const { isQuickActionVisible, isPinned, togglePin } = useUIPreferences();
const { available: availableQuickActions } = useAvailableQuickActions();

// Admin search
const { multiSearch, initialize: initializeSearch, isRateLimited, getDirectInstitutionIds } = useAdminSearch();

// Command actions
const { filterActions, actions: allCommandActions } = useCommandActions();

// Local state
const isSearching = ref(false);
const searchError = ref<string | null>(null);
const searchResults = ref<MultiSearchResults>(createEmptyMultiSearchResults());
const searchInputRef = ref<HTMLInputElement | null>(null);

// Show only the top 5 recent items in the palette
const topRecentItems = computed<RecentItem[]>(() => recentItems.value.slice(0, 5));

function resolveRecentIcon(item: RecentItem): Component {
  return resolvePageIcon(item.routeName, item.href);
}

// Context for mappers that need user-relative state (isRelated badges).
const mapperCtx = computed<MapperContext>(() => ({
  directInstitutionIds: [
    ...getDirectInstitutionIds('meetings'),
    ...getDirectInstitutionIds('agenda_items'),
  ],
}));

// Flat, interleaved hits (relevance-sorted when a query is present).
const flatHits = computed<NormalizedSearchHit[]>(() =>
  collectAllTabHits(searchResults.value, { query: query.value, dutyCtx: mapperCtx.value }),
);

// Filtered actions based on query — hide quick actions the user turned off
// or lacks permission for.
const filteredActions = computed(() => {
  let result = filterActions(query.value);

  const permittedKeys = new Set(availableQuickActions.value.map(m => m.key));

  result = result.filter((action) => {
    if (action.category !== 'create') {
      return true;
    }
    const map: Record<string, string> = {
      'create-meeting': 'new_meeting',
      'create-news': 'new_news',
      'create-reservation': 'new_reservation',
      'create-institution': 'new_institution',
      'create-duty': 'duty_update',
    };
    const qaKey = map[action.id];
    if (!qaKey) {
      return true;
    }
    return permittedKeys.has(qaKey) && isQuickActionVisible(qaKey as any);
  });

  return result.slice(0, 6); // Limit to 6 actions
});

// Check if we have any results
const hasResults = computed(() => {
  return (
    filteredActions.value.length > 0
    || flatHits.value.length > 0
  );
});

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

// Handle recent item selection
const handleRecentSelect = (item: RecentItem) => {
  close();

  if (item.href) {
    router.visit(item.href);
  }
  else if (item.type === 'action') {
    // For actions, find the action and execute it
    const actions = filterActions('');
    const action = actions.find(a => a.id === item.id);
    if (action) {
      action.action();
    }
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
      return $t('Įvykis');
    case 'institution':
      return $t('Institucija');
    case 'document':
      return $t('Dokumentas');
    default:
      return '';
  }
};
</script>
