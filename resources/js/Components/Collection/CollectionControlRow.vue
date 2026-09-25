<template>
  <div class="flex items-center gap-2 lg:gap-3" data-slot="collection-control-row">
    <div class="relative min-w-0 flex-1">
      <Search
        class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
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
        :class="[searchFieldClass, 'bg-secondary/40 text-base md:text-sm [&::-webkit-search-cancel-button]:hidden']"
        @input="emit('search', text)"
        @keydown.enter="emit('search', text, true)"
      >
      <button
        v-if="text"
        type="button"
        class="absolute top-1/2 right-1 flex size-9 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-11"
        :aria-label="$t('Išvalyti paiešką')"
        @click="clearText"
      >
        <X class="size-4" aria-hidden="true" />
      </button>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      <!-- Filled only while the filters are showing; set filters are told by the count alone. -->
      <button
        v-if="hasFilters"
        type="button"
        :aria-label="$t('Filtrai')"
        :aria-expanded="filtersOpen"
        :class="[controlVariants({ active: filtersOpen, voice: 'sentence' }), 'max-md:px-3']"
        data-slot="collection-filters-toggle"
        @click="emit('toggleFilters')"
      >
        <SlidersHorizontal class="size-4" aria-hidden="true" />
        <span class="sr-only md:not-sr-only">{{ $t('Filtrai') }}</span>
        <span v-if="activeFilterCount > 0" :class="controlCountClass">
          {{ activeFilterCount }}
        </span>
      </button>

      <slot name="view-toggle" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Search, SlidersHorizontal, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import { controlCountClass, controlVariants, searchFieldClass } from '@/Components/ui/control';

const props = defineProps<{
  /** The committed query; the input follows it when it changes from outside (URL, "Išvalyti visus"). */
  query: string;
  placeholder: string;
  hasFilters: boolean;
  filtersOpen: boolean;
  activeFilterCount: number;
}>();

const emit = defineEmits<{
  search: [query: string, immediate?: boolean];
  toggleFilters: [];
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
