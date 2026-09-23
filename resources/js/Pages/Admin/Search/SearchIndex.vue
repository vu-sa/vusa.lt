<template>
  <OverviewPage :eyebrow="$t('Paieška')" :title="$t('Ieškoti visur')" :head-title="$t('Paieška')">
    <div class="flex flex-col gap-3">
      <div class="relative">
        <Search
          class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-muted-foreground"
          aria-hidden="true"
        />
        <input
          ref="searchInputRef"
          v-model="q"
          type="search"
          data-admin-collection-search
          :placeholder="$t('Ieškoti visur...')"
          :aria-label="$t('Ieškoti visur')"
          autocomplete="off"
          :class="[
            'h-12 w-full border border-input bg-background pr-11 pl-11 text-base pointer-coarse:h-14',
            'placeholder:text-muted-foreground [&::-webkit-search-cancel-button]:hidden',
            'focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-ring',
          ]"
        >
        <button
          v-if="q"
          type="button"
          class="absolute top-1/2 right-1 flex size-10 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-12"
          :aria-label="$t('Išvalyti paiešką')"
          @click="clearQuery"
        >
          <X class="size-4" aria-hidden="true" />
        </button>
      </div>

      <Link
        v-if="retainedTab"
        :href="route('search.index', q.trim() ? { q: q.trim() } : {})"
        class="w-fit text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline pointer-coarse:py-2"
      >
        ‹ {{ $t('Visi rezultatai') }}
      </Link>
    </div>

    <!-- Agenda items and resources have no collection page of their own yet, so their tab still lives here. -->
    <div v-if="retainedTab" class="flex h-[calc(100dvh-22rem)] min-h-[360px] flex-col">
      <SearchCollectionPanel
        :key="retainedTab.collection"
        :collection="retainedTab.collection"
        :query="q"
        :empty-message="retainedTab.emptyMessage"
      />
    </div>

    <template v-else>
      <CollectionSkeleton v-if="isSearching && !hasSearched" :rows="4" />

      <p v-else-if="error" class="border-t border-border pt-3 text-sm text-status-danger" role="alert">
        {{ error }}
      </p>

      <EmptyState
        v-else-if="hasSearched && groups.length === 0"
        mode="no-results"
        :title="$t('Rezultatų nerasta')"
        :description="$t('Pabandyk kitą žodį ar trumpesnę frazę.')"
        :clear-label="q ? $t('Išvalyti paiešką') : undefined"
        @clear="clearQuery"
      />

      <template v-else>
        <SearchResultGroup
          v-for="group in groups"
          :key="group.key"
          :collection="group.key"
          :hits="group.hits"
          :total="group.total"
          :href="seeAllHref(destinations[group.key], q)"
        />
      </template>
    </template>
  </OverviewPage>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Search, X } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

import SearchCollectionPanel from '@/Features/Admin/AdminSearch/Components/SearchCollectionPanel.vue';
import SearchResultGroup from '@/Features/Admin/AdminSearch/Components/SearchResultGroup.vue';
import type { AdminCollection } from '@/Features/Admin/AdminSearch/Types/AdminSearchTypes';
import { seeAllHref, type SearchDestination } from '@/Features/Admin/AdminSearch/Utils/seeAllHref';
import {
  ALL_TAB_COLLECTION_ORDER,
  normalizeHit,
  type MapperContext,
  type NormalizedSearchHit,
  type SearchCollectionKey,
} from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { CollectionSkeleton, EmptyState } from '@/Components/Patterns';
import { useAdminSearch } from '@/Composables/useAdminSearch';
import type { MultiSearchResults } from '@/Shared/Search/types';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

defineProps<{
  /** Where each group's full list lives (and the query key it reads); built by AdminSearchDestinations. */
  destinations: Record<SearchCollectionKey, SearchDestination>;
}>();

const PER_GROUP = 5;

// The two collections without a page of their own keep their single-collection view.
const RETAINED_TABS: Record<string, { collection: AdminCollection; emptyMessage: string }> = {
  'agenda-items': { collection: 'agenda_items', emptyMessage: $t('Nerasta darbotvarkės punktų pagal jūsų paiešką') },
  'resources': { collection: 'resources', emptyMessage: $t('Nerasta išteklių pagal jūsų paiešką') },
};

const adminSearch = useAdminSearch();

// URL-backed state: `?q=` while grouped, `?tab=` only for a retained view.
const initialParams = new URLSearchParams(window.location.search);
const q = ref(initialParams.get('q') ?? '');
const tab = ref(initialParams.get('tab'));
const searchInputRef = ref<HTMLInputElement | null>(null);

const retainedTab = computed(() => (tab.value ? RETAINED_TABS[tab.value] ?? null : null));

const results = ref<MultiSearchResults>(createEmptyMultiSearchResults());
const isSearching = ref(false);
const hasSearched = ref(false);
const error = ref<string | null>(null);

// Mappers flag related-institution and cross-tenant rows relative to the user.
const mapperContext = computed<MapperContext>(() => ({
  ownTenantIds: adminSearch.getCollectionTenantIds('duties'),
  isSuperAdmin: adminSearch.isSuperAdmin.value,
  directInstitutionIds: [
    ...adminSearch.getDirectInstitutionIds('meetings'),
    ...adminSearch.getDirectInstitutionIds('agenda_items'),
  ],
}));

interface Group {
  key: SearchCollectionKey;
  hits: NormalizedSearchHit[];
  total: number;
}

// Static order, so a group never jumps under the cursor while the user types.
const groups = computed<Group[]>(() =>
  ALL_TAB_COLLECTION_ORDER
    .map(key => ({
      key,
      hits: (results.value[key] as unknown[]).map(doc => normalizeHit(key, doc, mapperContext.value)),
      total: results.value.counts[key] ?? 0,
    }))
    .filter(group => group.hits.length > 0),
);

const runMultiSearch = useDebounceFn(async (query: string) => {
  if (adminSearch.isRateLimited.value) {
    error.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.');
    isSearching.value = false;
    return;
  }

  isSearching.value = true;
  error.value = null;
  try {
    results.value = await adminSearch.multiSearch(query, {
      meetingsLimit: PER_GROUP,
      agendaItemsLimit: PER_GROUP,
      institutionsLimit: PER_GROUP,
      resourcesLimit: PER_GROUP,
      dutiesLimit: PER_GROUP,
      documentsLimit: PER_GROUP,
      newsLimit: PER_GROUP,
      pagesLimit: PER_GROUP,
      calendarLimit: PER_GROUP,
      usersLimit: PER_GROUP,
    });
    hasSearched.value = true;
  }
  catch (err) {
    const message = err instanceof Error ? err.message : 'Search failed';
    error.value = message.includes('Too many requests')
      ? $t('Per daug užklausų. Palaukite ir bandykite vėliau.')
      : message;
  }
  finally {
    isSearching.value = false;
  }
}, 300);

function writeUrl(): void {
  const params = new URLSearchParams();
  if (q.value.trim()) {
    params.set('q', q.value.trim());
  }
  if (retainedTab.value && tab.value) {
    params.set('tab', tab.value);
  }
  const queryString = params.toString();
  window.history.replaceState({}, '', `${window.location.pathname}${queryString ? `?${queryString}` : ''}`);
}

function clearQuery(): void {
  q.value = '';
  searchInputRef.value?.focus();
}

watch(q, () => {
  // A retained view syncs the URL through its own controller after searching.
  if (!retainedTab.value) {
    writeUrl();
    isSearching.value = true;
    runMultiSearch(q.value);
  }
});

onMounted(async () => {
  searchInputRef.value?.focus();
  await adminSearch.initialize();

  // A tab this user cannot search (no scoped key) falls back to the grouped view.
  const current = retainedTab.value;
  if (current && !adminSearch.hasCollectionAccess(current.collection)) {
    tab.value = null;
  }

  if (!retainedTab.value) {
    isSearching.value = true;
    runMultiSearch(q.value);
  }
});
</script>
