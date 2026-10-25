<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <!-- Toolbar (count, pills, filters, sort) — fixed reserved height keeps the
         result list from shifting when a panel supplies a taller toolbar. -->
    <div v-if="$slots.toolbar" class="mb-3 flex min-h-9 shrink-0 flex-col justify-center">
      <slot name="toolbar" />
    </div>

    <!-- Split panes -->
    <div class="flex min-h-0 flex-1 gap-4">
      <!-- List -->
      <div
        :class="[
          'min-h-0 border border-border bg-background',
          'w-full lg:w-2/5 lg:max-w-md',
          mobileShowsDetail ? 'hidden lg:block' : 'block',
        ]"
      >
        <SearchResultList
          :hits
          :selected-id="selectedHit?.id"
          :is-loading
          :is-loading-more
          :has-more
          :has-searched
          :error
          :empty-message
          :selectable
          :multiple
          :selected-ids
          :disabled-ids
          @select="onSelect"
          @toggle="$emit('toggleSelect', $event)"
          @load-more="$emit('loadMore')"
        />
      </div>

      <!-- Detail -->
      <div
        :class="[
          'min-h-0 flex-1 border border-border bg-background',
          mobileShowsDetail ? 'block' : 'hidden lg:block',
        ]"
      >
        <!-- Mobile back button -->
        <div class="border-b border-border p-2 lg:hidden">
          <Button variant="ghost" size="sm" voice="sentence" @click="mobileShowsDetail = false">
            <ChevronLeft aria-hidden="true" />
            {{ $t('Atgal į sąrašą') }}
          </Button>
        </div>
        <slot name="detail" :hit="selectedHit">
          <SearchDetailPane :hit="selectedHit" />
        </slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft } from '@lucide/vue';

import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import SearchResultList from './SearchResultList.vue';
import SearchDetailPane from './SearchDetailPane.vue';

import { Button } from '@/Components/ui/button';
import { useListDetailSelection } from '@/Composables/useListDetailSelection';

const props = withDefaults(defineProps<{
  hits: NormalizedSearchHit[];
  selectedHit: NormalizedSearchHit | null;
  isLoading?: boolean;
  isLoadingMore?: boolean;
  hasMore?: boolean;
  hasSearched?: boolean;
  error?: string | null;
  emptyMessage?: string;
  /** Selection mode: rows show a checkbox/radio and Enter toggles instead of navigating. */
  selectable?: boolean;
  multiple?: boolean;
  /** Ids currently in the selection. */
  selectedIds?: Set<string>;
  /** Ids that cannot be toggled. */
  disabledIds?: Set<string>;
}>(), {
  isLoading: false,
  isLoadingMore: false,
  hasMore: false,
  hasSearched: false,
  error: null,
  emptyMessage: '',
  selectable: false,
  multiple: false,
});

const emit = defineEmits<{
  'update:selectedHit': [hit: NormalizedSearchHit | null];
  'toggleSelect': [hit: NormalizedSearchHit];
  'loadMore': [];
}>();

const mobileShowsDetail = ref(false);

const onSelect = (hit: NormalizedSearchHit) => {
  emit('update:selectedHit', hit);
  mobileShowsDetail.value = true;
};

useListDetailSelection({
  items: () => props.hits,
  itemKey: hit => hit.id,
  selectedKey: () => props.selectedHit?.id ?? null,
  select: key => emit('update:selectedHit', props.hits.find(hit => hit.id === key) ?? null),
  arrowsFromInput: true,
  onEnter: (hit) => {
    // In selection mode Enter toggles the highlighted hit rather than navigating.
    if (props.selectable) {
      if (!props.disabledIds?.has(hit.id)) {
        emit('toggleSelect', hit);
      }
      return;
    }
    if (!hit.href) {
      return;
    }
    // External links (document URLs) open in a new tab; internal routes stay in the SPA.
    if (/^https?:\/\//.test(hit.href)) {
      window.open(hit.href, '_blank', 'noopener');
    }
    else {
      router.visit(hit.href);
    }
  },
});
</script>
