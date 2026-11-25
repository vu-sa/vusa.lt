<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <div class="mb-3 flex h-11 shrink-0 items-center gap-2 border border-border bg-background px-3 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand/20">
      <Search v-if="!isSearching" class="size-4 shrink-0 text-muted-foreground/60" />
      <LoaderCircle v-else class="size-4 shrink-0 animate-spin text-brand" aria-hidden="true" />
      <input
        :value="query"
        type="text"
        :placeholder="searchPlaceholder"
        :aria-label="searchPlaceholder"
        class="h-full min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground/70"
        @input="onQueryInput"
      >
    </div>

    <SearchSplitView
      v-model:selected-hit="selectedHit"
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
import { LoaderCircle, Search } from '@lucide/vue';

import { useAdminSearch } from '@/Composables/useAdminSearch';
import type { MultiSearchResults } from '@/Search/core/types';
import { adminSearchRequests, collectAllTabHits, type AdminSearchCollectionId, type NormalizedSearchHit } from '@/Components/Search/adminSearchCollections';

import SearchSplitView from '../Search/SearchSplitView.vue';

const props = defineProps<{
  collections: AdminSearchCollectionId[];
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
const results = ref<MultiSearchResults<AdminSearchCollectionId>>({});

const selectedHit = ref<NormalizedSearchHit | null>(null);

const resultHits = computed(() => collectAllTabHits(results.value, { query: query.value }));

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
    errorMessage.value = $t('Per daug užklausų. Palauk ir bandyk vėliau.');
    isSearching.value = false;
    return;
  }

  isSearching.value = true;
  errorMessage.value = null;

  try {
    results.value = await adminSearch.multiSearch(query.value, adminSearchRequests(props.collections, 15));
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
