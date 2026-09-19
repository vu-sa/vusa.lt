<template>
  <div class="flex flex-col gap-2 lg:flex-row lg:items-center" data-slot="collection-control-row">
    <div class="relative min-w-0 flex-1">
      <Search
        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        aria-hidden="true"
      />
      <!-- AdminLayout's `/` shortcut focuses the first field carrying this attribute (U3). -->
      <input
        v-model="text"
        type="search"
        data-admin-collection-search
        :placeholder
        :aria-label="placeholder"
        autocomplete="off"
        :class="[
          'h-9 w-full border border-input bg-background pr-9 pl-9 text-base md:text-sm pointer-coarse:h-11',
          'placeholder:text-muted-foreground [&::-webkit-search-cancel-button]:hidden',
          'focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-ring',
        ]"
        @input="emit('search', text)"
        @keydown.enter="emit('search', text, true)"
      >
      <button
        v-if="text"
        type="button"
        class="absolute top-1/2 right-1 flex size-8 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-10"
        :aria-label="$t('Išvalyti paiešką')"
        @click="clearText"
      >
        <X class="size-4" aria-hidden="true" />
      </button>
    </div>

    <div class="grid grid-cols-[minmax(0,1fr)_auto_auto] items-center gap-2 lg:flex">
      <button
        v-if="hasFilters"
        type="button"
        :aria-expanded="filtersOpen"
        class="inline-flex h-9 items-center justify-center gap-2 border border-border bg-background px-3 text-sm hover:border-foreground/40 pointer-coarse:h-11"
        @click="emit('toggleFilters')"
      >
        <SlidersHorizontal class="size-4" aria-hidden="true" />
        <span>{{ $t('Filtrai') }}</span>
        <span
          v-if="activeFilterCount > 0"
          class="min-w-5 bg-brand-fill px-1 text-center text-xs tabular-nums text-brand-foreground"
        >
          {{ activeFilterCount }}
        </span>
      </button>

      <label v-if="sortOptions.length > 0" class="inline-flex items-center gap-2">
        <span class="sr-only">{{ $t('Rikiuoti') }}</span>
        <select
          :value="sortBy"
          class="h-9 max-w-44 border border-border bg-background px-2 text-sm pointer-coarse:h-11"
          @change="emit('update:sortBy', ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="option in sortOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </label>

      <slot name="view-toggle" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import type { CollectionSortOption } from '@/Composables/useCollectionSource';

const props = defineProps<{
  /** The committed query; the input follows it when it changes from outside (URL, "Išvalyti visus"). */
  query: string;
  placeholder: string;
  hasFilters: boolean;
  filtersOpen: boolean;
  activeFilterCount: number;
  sortBy: string;
  sortOptions: CollectionSortOption[];
}>();

const emit = defineEmits<{
  'search': [query: string, immediate?: boolean];
  'toggleFilters': [];
  'update:sortBy': [value: string];
}>();

const text = ref(props.query);

watch(() => props.query, (query) => {
  if (query !== text.value) {
    text.value = query;
  }
});

function clearText(): void {
  text.value = '';
  emit('search', '', true);
}
</script>
