<template>
  <SearchSplitView
    v-model:selected-hit="selectedHit"
    :hits="hits"
    :is-loading="isSearching"
    :has-searched="hasSearched"
    :error="error"
    :empty-message="$t('Rezultatų nerasta')"
  >
    <template #toolbar>
      <p class="text-sm text-muted-foreground">
        <span v-if="totalHits > 0">{{ $t('Rasta :count rezultatų', { count: String(totalHits) }) }}</span>
      </p>
    </template>
  </SearchSplitView>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import SearchSplitView from './SearchSplitView.vue';
import { ALL_TAB_COLLECTION_ORDER, collectAllTabHits, type MapperContext, type NormalizedSearchHit } from '../Utils/searchHitMappers';
import type { MultiSearchResults } from '@/Shared/Search/types';

const props = defineProps<{
  results: MultiSearchResults;
  /** Current search query; drives relevance interleaving vs. browse ordering. */
  query?: string;
  isSearching: boolean;
  hasSearched: boolean;
  error: string | null;
  /** Context for the duties mapper (flags cross-tenant rows). */
  dutyCtx?: MapperContext;
}>();

const selectedHit = ref<NormalizedSearchHit | null>(null);

const hits = computed<NormalizedSearchHit[]>(() =>
  collectAllTabHits(props.results, { query: props.query, dutyCtx: props.dutyCtx }),
);

const totalHits = computed(() =>
  ALL_TAB_COLLECTION_ORDER.reduce((sum, key) => sum + (props.results.counts[key] ?? 0), 0),
);
</script>
