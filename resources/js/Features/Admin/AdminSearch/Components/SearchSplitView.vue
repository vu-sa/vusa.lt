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
          'min-h-0 rounded-lg border bg-card',
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
          'min-h-0 flex-1 rounded-lg border bg-card',
          mobileShowsDetail ? 'block' : 'hidden lg:block',
        ]"
      >
        <!-- Mobile back button -->
        <div class="border-b p-2 lg:hidden">
          <Button variant="ghost" size="sm" @click="mobileShowsDetail = false">
            <ChevronLeft class="mr-1 size-4" />
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
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useEventListener } from '@vueuse/core';
import { ChevronLeft } from 'lucide-vue-next';

import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import SearchResultList from './SearchResultList.vue';
import SearchDetailPane from './SearchDetailPane.vue';

import { Button } from '@/Components/ui/button';

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

// Auto-select the first result when the set changes and the current selection is gone.
watch(
  () => props.hits,
  (hits) => {
    if (hits.length === 0) {
      if (props.selectedHit !== null) {
        emit('update:selectedHit', null);
      }
      return;
    }
    const stillPresent = props.selectedHit && hits.some(h => h.id === props.selectedHit!.id);
    if (!stillPresent) {
      emit('update:selectedHit', hits[0]);
    }
  },
  { deep: false },
);

// Keyboard navigation (up/down move selection, Enter opens) when not typing in a field.
useEventListener('keydown', (event: KeyboardEvent) => {
  if (!['ArrowDown', 'ArrowUp', 'Enter'].includes(event.key)) {
    return;
  }
  const target = event.target as HTMLElement | null;
  const typing = target && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

  if (event.key === 'Enter') {
    if (typing) {
      return;
    }
    // In selection mode Enter toggles the highlighted hit rather than navigating.
    if (props.selectable) {
      if (props.selectedHit && !props.disabledIds?.has(props.selectedHit.id)) {
        event.preventDefault();
        emit('toggleSelect', props.selectedHit);
      }
      return;
    }
    const href = props.selectedHit?.href;
    if (href) {
      // External links (e.g. document URLs) open in a new tab; internal
      // routes use Inertia so we keep the SPA navigation.
      if (/^https?:\/\//.test(href)) {
        window.open(href, '_blank', 'noopener');
      }
      else {
        router.visit(href);
      }
    }
    return;
  }
  if (typing && target?.tagName !== 'INPUT') {
    return;
  }
  if (props.hits.length === 0) {
    return;
  }
  event.preventDefault();
  const currentIndex = props.selectedHit
    ? props.hits.findIndex(h => h.id === props.selectedHit!.id)
    : -1;
  const delta = event.key === 'ArrowDown' ? 1 : -1;
  const nextIndex = Math.min(props.hits.length - 1, Math.max(0, currentIndex + delta));
  emit('update:selectedHit', props.hits[nextIndex]);
});
</script>
