<template>
  <div class="flex h-full flex-col">
    <!-- Error -->
    <div
      v-if="error"
      class="m-3 border border-status-attention-border bg-status-attention-surface p-3"
    >
      <p class="flex items-center gap-2 text-sm text-status-attention">
        <Clock class="size-4 shrink-0" />
        {{ error }}
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="isLoading && hits.length === 0" class="space-y-1 p-2">
      <div v-for="i in 8" :key="i" class="flex items-center gap-3 px-3 py-2.5">
        <div class="size-10 animate-pulse bg-muted/50" />
        <div class="flex-1 space-y-2">
          <div class="h-4 w-3/4 animate-pulse bg-muted/50" />
          <div class="h-3 w-1/2 animate-pulse bg-muted/30" />
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="hits.length === 0" class="flex flex-1 flex-col items-center justify-center px-6 py-16 text-center">
      <div class="mb-4 flex size-12 items-center justify-center bg-muted/50">
        <component :is="hasSearched ? SearchX : Sparkles" class="size-6 text-muted-foreground/50" />
      </div>
      <p class="text-sm font-medium text-foreground">
        {{ hasSearched ? emptyMessage : $t('Pradėk rašyti') }}
      </p>
      <p class="mt-1 text-xs text-muted-foreground">
        {{ hasSearched ? $t('Pabandyk kitą paieškos frazę') : $t('Ieškok visose tau prieinamose srityse vienu metu') }}
      </p>
    </div>

    <!-- Results -->
    <div v-else class="min-h-0 flex-1 overflow-y-auto p-2">
      <SearchResultListItem
        v-for="hit in hits"
        :key="hit.id"
        :hit
        :selected="hit.id === selectedId"
        :selectable
        :multiple
        :checked="selectedIds?.has(hit.id)"
        :disabled="disabledIds?.has(hit.id)"
        @select="$emit('select', hit)"
        @toggle="$emit('toggle', hit)"
      />

      <div v-if="hasMore" class="mt-2 px-1 pb-1">
        <Button variant="outline" voice="sentence" size="sm" class="w-full" :disabled="isLoadingMore" @click="$emit('loadMore')">
          <Loader2 v-if="isLoadingMore" class="mr-2 size-4 animate-spin" />
          {{ $t('Rodyti daugiau') }}
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Clock, Sparkles, SearchX, Loader2 } from 'lucide-vue-next';

import type { NormalizedSearchHit } from '../Utils/searchHitMappers';

import SearchResultListItem from './SearchResultListItem.vue';

import { Button } from '@/Components/ui/button';

withDefaults(defineProps<{
  hits: NormalizedSearchHit[];
  selectedId?: string;
  isLoading?: boolean;
  isLoadingMore?: boolean;
  hasMore?: boolean;
  hasSearched?: boolean;
  error?: string | null;
  emptyMessage?: string;
  /** Selection mode (checkbox/radio affordance per row). */
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

defineEmits<{
  select: [hit: NormalizedSearchHit];
  toggle: [hit: NormalizedSearchHit];
  loadMore: [];
}>();
</script>
