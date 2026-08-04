<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <div class="mb-3 flex h-10 shrink-0 items-center gap-2 rounded-lg border bg-background px-3">
      <Search v-if="!isSearching" class="size-4 shrink-0 text-muted-foreground/60" />
      <div v-else class="size-4 shrink-0 animate-spin rounded-full border-2 border-primary/30 border-t-primary" />
      <input
        :value="query"
        type="text"
        :placeholder="searchPlaceholder"
        class="h-full flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/50"
        @input="onQueryInput"
      >
    </div>

    <SearchSplitView
      :hits
      :is-loading="isSearching"
      :has-searched
      :error="errorMessage"
      :empty-message
      selectable
      :multiple
      :selected-ids
      :disabled-ids
      @toggle-select="$emit('toggle', $event)"
    >
      <template #toolbar>
        <span v-if="hasSearched" class="text-sm text-muted-foreground">
          {{ $t('Rasta :count rezultatų', { count: String(hits.length) }) }}
        </span>
      </template>
    </SearchSplitView>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { useDebounceFn } from '@vueuse/core';
import { Search } from 'lucide-vue-next';

import { useAdminSearch } from '@/Composables/useAdminSearch';
import type { MultiSearchResults } from '@/Shared/Search/types';
import { createEmptyMultiSearchResults } from '@/Shared/Search/utils/createEmptyMultiSearchResults';

import SearchSplitView from '../SearchSplitView.vue';
import { collectAllTabHits, type NormalizedSearchHit } from '../../Utils/searchHitMappers';
import type { AdminCollection } from '../../Types/AdminSearchTypes';

const props = defineProps<{
  /** Which collections to search and offer — everything else is filtered out of the
   * results, since a multi-search hits every collection the user can access. */
  collections: AdminCollection[];
  multiple: boolean;
  selectedIds: Set<string>;
  emptyMessage: string;
  searchPlaceholder: string;
  disabledIds?: Set<string>;
  /** Currently-selected hits to surface first while browsing (no query yet). */
  pinnedHits?: NormalizedSearchHit[];
}>();

defineEmits<{
  toggle: [hit: NormalizedSearchHit];
}>();

const adminSearch = useAdminSearch();

const query = ref('');
const isSearching = ref(false);
const hasSearched = ref(false);
const errorMessage = ref<string | null>(null);
const results = ref<MultiSearchResults>(createEmptyMultiSearchResults());

const allowedCollections = new Set(props.collections);

const resultHits = computed<NormalizedSearchHit[]>(() =>
  collectAllTabHits(results.value, { query: query.value })
    .filter(hit => allowedCollections.has(hit.collection)));

// While browsing (no query) the currently-selected hits are pinned to the top, same
// behaviour as the single-collection picker (SearchSelectView).
const hits = computed<NormalizedSearchHit[]>(() => {
  const pinned = props.pinnedHits ?? [];
  if (pinned.length === 0 || query.value.trim() !== '') {
    return resultHits.value;
  }
  const resultIds = new Set(resultHits.value.map(h => h.id));
  const pinnedIds = new Set(pinned.map(h => h.id));
  const pinnedNotInResults = pinned.filter(h => !resultIds.has(h.id));
  const pinnedFromResults = resultHits.value.filter(h => pinnedIds.has(h.id));
  const rest = resultHits.value.filter(h => !pinnedIds.has(h.id));
  return [...pinnedNotInResults, ...pinnedFromResults, ...rest];
});

const runSearch = useDebounceFn(async () => {
  if (adminSearch.isRateLimited.value) {
    errorMessage.value = $t('Per daug užklausų. Palaukite ir bandykite vėliau.');
    isSearching.value = false;
    return;
  }

  isSearching.value = true;
  errorMessage.value = null;

  try {
    results.value = await adminSearch.multiSearch(query.value, {
      pagesLimit: 15,
      newsLimit: 15,
      calendarLimit: 15,
      institutionsLimit: 15,
      documentsLimit: 15,
      // Not offered as link targets — keep the request light.
      meetingsLimit: 0,
      agendaItemsLimit: 0,
      resourcesLimit: 0,
      dutiesLimit: 0,
      usersLimit: 0,
    });
    hasSearched.value = true;
  }
  catch (err) {
    errorMessage.value = err instanceof Error ? err.message : 'Search failed';
  }
  finally {
    isSearching.value = false;
  }
}, 300);

const onQueryInput = (event: Event) => {
  query.value = (event.target as HTMLInputElement).value;
  runSearch();
};

onMounted(() => {
  // Browse mode: show results immediately, not just after the first keystroke.
  runSearch();
});
</script>
