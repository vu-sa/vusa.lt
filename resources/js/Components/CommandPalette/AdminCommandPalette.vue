<template>
  <PaletteDialog v-model:open="isOpen" :title="$t('Komandų paletė')"
    :description="$t('Ieškok veiksmų, posėdžių ir darbotvarkės klausimų')">
    <!-- Custom search input (not CommandInput to avoid internal filtering) -->
    <div class="relative flex h-14 items-center gap-3 border-b border-border px-4 pr-12">
      <Search class="size-4 shrink-0 text-muted-foreground" />
      <input ref="searchInputRef" v-model="query" type="text" :placeholder="$t('shell.chrome.search_field')"
        :aria-label="$t('shell.chrome.search_field')"
        class="h-14 flex-1 bg-transparent text-base outline-none placeholder:text-muted-foreground sm:text-sm"
        @keydown.escape="close"
        @keydown.down.prevent="focusFirstResult"
        @keydown.enter.prevent="selectFirstResult">
      <TopProgressBar :active="isSearching" class="absolute inset-x-0 -bottom-px" />
    </div>

    <CommandList class="max-h-[50vh] scroll-py-2 max-sm:max-h-none max-sm:flex-1 sm:max-h-[60vh]">
      <!-- Pinned pages, then recents: both empty-state only (O20, O15) -->
      <PaletteGroup v-if="!searchTerm && pinnedItems.length > 0" :heading="$t('shell.palette.pinned')">
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
      </PaletteGroup>

      <PaletteGroup v-if="!searchTerm && personalItems.length > 0" :heading="$t('shell.palette.personal')">
        <PaletteRow
          v-for="item in personalItems" :key="`${item.kind}-${item.id}`"
          :value="`personal-${item.kind}-${item.id}`"
          :icon="resolvePageIcon(undefined, item.href)"
          :entity-type="item.kind === 'duty' ? ModelEnum.DUTY : ModelEnum.INSTITUTION"
          :title="item.title"
          :subtitle="personalSubtitle(item)"
          @select="navigateToHref(item.href)"
        />
      </PaletteGroup>

      <PaletteGroup v-if="!searchTerm && topRecentItems.length > 0" :heading="$t('shell.palette.recent')">
        <PaletteRow
          v-for="item in topRecentItems" :key="`recent-${item.type}-${item.id}`"
          :value="`recent-${item.type}-${item.id}`"
          :icon="resolvePageIcon(item.routeName, item.href)"
          :entity-type="recentEntityType(item.type)"
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
      </PaletteGroup>

      <PaletteGroup v-if="createActions.length > 0" :heading="$t('shell.palette.create')">
        <ActionResult v-for="action in createActions" :key="action.id" :action />
      </PaletteGroup>

      <PaletteGroup v-if="otherActions.length > 0" :heading="$t('shell.palette.actions')">
        <ActionResult v-for="action in otherActions" :key="action.id" :action />
      </PaletteGroup>

      <!-- Go to: the current workspace first -->
      <PaletteGroup v-if="goToActions.length > 0" :heading="$t('shell.palette.go_to')">
        <ActionResult v-for="action in goToActions" :key="action.id" :action />
      </PaletteGroup>

      <!-- Wiki articles come from the small index the Wiki hub searches too, not from Typesense. -->
      <PaletteGroup v-if="wikiHits.length > 0" :heading="$t('learning.pages.knowledge')">
        <PaletteRow
          v-for="page in wikiHits" :key="`wiki-${page.id}`"
          :value="`wiki-${page.id}`"
          :icon="Library"
          :entity-type="ModelEnum.KNOWLEDGE_ITEM"
          :title="page.abbreviation ? `${page.title} (${page.abbreviation})` : page.title"
          :subtitle="$t(`learning.kinds.${page.kind}`)"
          @select="navigateToHref(page.url)"
        />
      </PaletteGroup>
      <!-- One ranked list across collections; each row names its type -->
      <PaletteGroup v-if="flatHits.length > 0" :heading="$t('shell.palette.results')">
        <CommandItem
          v-for="hit in flatHits"
          :key="hit.id"
          :value="hit.id"
          :class="paletteRowClass"
          @select="handleHitSelect(hit)"
        >
          <SearchHitRow
            :hit
            show-actions
            show-type
            @view="navigateToHref(hit.viewHref)"
            @edit="navigateToHref(hit.editHref)"
          />
        </CommandItem>
      </PaletteGroup>

      <!-- Rate limit warning -->
      <div v-if="searchError && searchError.includes('užklausų')" class="mx-4 my-2 border border-status-attention-border bg-status-attention-surface p-3">
        <p class="flex items-center gap-2 text-sm text-status-attention">
          <Clock class="size-4" />
          {{ searchError }}
        </p>
      </div>

      <!-- Empty state -->
      <div v-if="searchTerm && !hasResults && !isSearching && !searchError" class="py-14 text-center">
        <div class="mx-auto mb-4 flex size-12 items-center justify-center border border-border bg-secondary">
          <SearchX class="size-6 text-muted-foreground" />
        </div>
        <p class="text-sm font-medium text-foreground">
          {{ $t('Rezultatų nerasta') }}
        </p>
        <p class="mt-1 text-xs text-muted-foreground">
          {{ $t('Pabandyk kitą paieškos frazę') }}
        </p>
      </div>
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
  Library,
} from '@lucide/vue';

import { apiRequest } from '@/Composables/apiRequest';
import { useAdminSearch } from '@/Composables/useAdminSearch';
import type { MultiSearchResults } from '@/Search/core/types';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import { resolvePageIcon } from '@/Composables/pageIcons';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import { adminSearchRequests, collectAllTabHits, type AdminSearchCollectionId, type NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';
import type { MapperContext } from '@/Search/admin/Utils/searchHitMappers';
import SearchHitRow from '@/Components/Search/SearchHitRow.vue';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import { TopProgressBar } from '@/Components/Patterns';
import { CommandList, CommandItem } from '@/Components/ui/command';
import { loadWikiLinkTargets } from '@/Search/admin/useWikiLinkTargets';
import { searchWiki } from '@/Search/admin/wikiSearch';
import type { WikiIndexEntry } from '@/Types/learning';
import { ModelEnum } from '@/Types/enums';

import PinButton from './results/PinButton.vue';
import { paletteRowClass } from './results/paletteRowClass';
import PaletteRow from './results/PaletteRow.vue';
import PaletteGroup from './results/PaletteGroup.vue';
import ActionResult from './results/ActionResult.vue';
import { useCommandActions } from './useCommandActions';
import PaletteDialog from './PaletteDialog.vue';

// Command palette state
const { isOpen, query, recentItems, close } = useCommandPalette();

const { isPinned, togglePin, pinnedPages } = useUIPreferences();

// Admin search
const { multiSearch, initialize: initializeSearch, isRateLimited, getDirectInstitutionIds } = useAdminSearch();

// Command actions
const { filterActions, activeWorkspace } = useCommandActions();

// Local state
interface PersonalRecord {
  id: string;
  kind: 'assigned' | 'followed' | 'duty';
  title: string;
  context: string | null;
  href: string;
}

const searchTerm = computed(() => query.value.trim());
const isSearching = ref(false);
const searchError = ref<string | null>(null);
const searchResults = ref<MultiSearchResults<AdminSearchCollectionId>>({});
const searchInputRef = ref<HTMLInputElement | null>(null);

const pinnedItems = computed<RecentItem[]>(() => pinnedPages.value);
const linkPath = (href?: string) => href ? new URL(href, 'https://local.invalid').pathname : undefined;
const pinnedPaths = computed(() => new Set(pinnedItems.value.map(item => linkPath(item.href))));

const personalRecords = ref<PersonalRecord[]>([]);
const personalOrder = { assigned: 0, followed: 1, duty: 2 };
const personalItems = computed(() => personalRecords.value
  .filter(item => !pinnedPaths.value.has(linkPath(item.href)))
  .sort((a, b) => personalOrder[a.kind] - personalOrder[b.kind]
    || a.title.localeCompare(b.title, document.documentElement.lang || undefined)
    || a.id.localeCompare(b.id)));

const personalSubtitle = (item: PersonalRecord): string => [
  $t(`shell.palette.personal_${item.kind}`),
  item.context,
].filter(Boolean).join(' · ');

const wikiIndex = ref<WikiIndexEntry[]>([]);
const wikiHits = computed(() => searchTerm.value ? searchWiki(wikiIndex.value, searchTerm.value).slice(0, 4) : []);

const topRecentItems = computed<RecentItem[]>(() => {
  const shown = new Set([...pinnedPaths.value, ...personalItems.value.map(item => linkPath(item.href))]);

  return recentItems.value.filter(item => !shown.has(linkPath(item.href))).slice(0, 5);
});

// Context for mappers that need user-relative state (isRelated badges).
const mapperCtx = computed<MapperContext>(() => ({
  directInstitutionIds: [
    ...getDirectInstitutionIds('meetings'),
    ...getDirectInstitutionIds('agenda_items'),
  ],
}));

const flatHits = computed<NormalizedSearchHit[]>(() =>
  searchTerm.value ? collectAllTabHits(searchResults.value, { query: searchTerm.value, mapperContext: mapperCtx.value }) : [],
);

const matchingActions = computed(() => filterActions(searchTerm.value));
const createActions = computed(() => searchTerm.value ? matchingActions.value.filter(action => action.category === 'create') : []);
const otherActions = computed(() => searchTerm.value ? matchingActions.value.filter(action => action.category === 'action') : []);
const goToActions = computed(() => {
  const navigation = matchingActions.value.filter(action => action.category === 'navigation');

  return (searchTerm.value ? navigation : navigation.filter(action => action.workspaceKey === activeWorkspace.value?.key)).slice(0, 8);
});

// Check if we have any results
const hasResults = computed(() => (
  createActions.value.length > 0
  || otherActions.value.length > 0
  || goToActions.value.length > 0
  || flatHits.value.length > 0
  || wikiHits.value.length > 0
));

let searchVersion = 0;
const performSearch = useDebounceFn(async (term: string, version: number) => {
  if (version !== searchVersion) return;
  if (isRateLimited.value) {
    searchError.value = $t('Per daug užklausų. Palauk ir bandyk vėliau.');
    isSearching.value = false;
    return;
  }

  try {
    const results = await multiSearch(term, adminSearchRequests());
    if (version === searchVersion) searchResults.value = results;
  }
  catch (error) {
    if (version !== searchVersion) return;
    const message = error instanceof Error ? error.message : 'Search failed';
    searchError.value = message.includes('Too many requests')
      ? $t('Per daug užklausų. Palauk ir bandyk vėliau.')
      : message;
  }
  finally {
    if (version === searchVersion) isSearching.value = false;
  }
}, 300);

watch(searchTerm, (term, _previous, onCleanup) => {
  const version = ++searchVersion;
  onCleanup(() => {
    searchVersion++;
  });
  searchResults.value = {};
  searchError.value = null;
  isSearching.value = Boolean(term);
  if (term) performSearch(term, version);
}, { immediate: true });

// Track if search has been initialized to prevent duplicate calls
let searchInitialized = false;

// Initialize search and focus input when opened
watch(isOpen, (opened, _previous, onCleanup) => {
  if (opened) {
    const request = new AbortController();
    onCleanup(() => request.abort());
    personalRecords.value = [];
    apiRequest<PersonalRecord[]>(route('api.v1.admin.commandPalette.personalRecords'), { signal: request.signal }).then(({ data }) => {
      if (!request.signal.aborted) personalRecords.value = data ?? [];
    }).catch(() => {});
    // Only initialize once per session
    if (!searchInitialized) {
      initializeSearch();
      searchInitialized = true;
    }
    loadWikiLinkTargets().then((index) => {
      wikiIndex.value = index;
    });
    // Focus the search input after dialog opens with fallback for animation delay
    nextTick(() => {
      searchInputRef.value?.focus();
      // Fallback in case dialog animation delays rendering
      setTimeout(() => {
        searchInputRef.value?.focus();
      }, 50);
    });
  }
}, { immediate: true });

const resultList = () => searchInputRef.value?.closest('[data-slot="command"]')
  ?.querySelector<HTMLElement>('[data-slot="command-list"]');

const focusFirstResult = () => resultList()?.focus();
const selectFirstResult = () => resultList()?.querySelector<HTMLElement>('[role="option"]')?.click();

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

// Admin pages and actions have no record type; everything else reads the shared glossary.
const recentEntityType = (type: RecentItem['type']): string | undefined => (
  type === 'page' || type === 'action' ? undefined : type
);

const getRecentTypeBadge = (type: RecentItem['type']): string => {
  if (type === 'action') return $t('Veiksmas');
  if (type === 'page') return $t('Puslapis');
  const definition = getEntityTypeDefinition(type);
  return definition ? $t(definition.label) : '';
};
</script>
