<template>
  <div class="flex flex-col gap-3 lg:flex-row lg:items-center" data-slot="collection-control-row">
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

    <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap">
      <button
        v-if="hasFilters"
        type="button"
        :aria-expanded="filtersOpen"
        :class="controlVariants({ active: filtersOpen || activeFilterCount > 0 })"
        @click="emit('toggleFilters')"
      >
        <SlidersHorizontal class="size-4" aria-hidden="true" />
        <span>{{ $t('Filtrai') }}</span>
        <span v-if="activeFilterCount > 0" :class="controlCountClass">
          {{ activeFilterCount }}
        </span>
      </button>

      <label
        v-if="sortOptions.length > 1"
        :class="[
          'relative flex h-11 min-w-0 items-center border border-border bg-background pr-9 pl-3',
          'focus-within:border-brand pointer-coarse:min-h-11',
        ]"
      >
        <span class="mr-2 shrink-0 text-[11px] font-bold uppercase tracking-wide text-muted-foreground">
          {{ $t('Rikiuoti') }}
        </span>
        <select
          :value="sortBy"
          :aria-label="$t('Rikiuoti')"
          class="w-full max-w-48 min-w-0 appearance-none truncate bg-transparent text-sm font-bold text-foreground outline-none"
          @change="emit('update:sortBy', ($event.target as HTMLSelectElement).value)"
        >
          <option v-for="option in sortOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <ChevronDown class="pointer-events-none absolute right-3 size-4 text-muted-foreground" aria-hidden="true" />
      </label>

      <button
        v-if="trash && (trash.active || trash.count > 0)"
        type="button"
        :aria-pressed="trash.active"
        :class="controlVariants({ active: trash.active })"
        @click="emit('toggleTrash')"
      >
        <Trash2 class="size-4" aria-hidden="true" />
        <span>{{ $t('Ištrinti') }}</span>
        <span v-if="trash.count > 0" :class="trash.active ? controlCountClass : 'tabular-nums text-muted-foreground'">
          {{ trash.count }}
        </span>
      </button>

      <slot name="view-toggle" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, Search, SlidersHorizontal, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

import type { CollectionTrash } from './types';

import { controlCountClass, controlVariants, searchFieldClass } from '@/Components/ui/control';
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
  trash?: CollectionTrash;
}>();

const emit = defineEmits<{
  'search': [query: string, immediate?: boolean];
  'toggleFilters': [];
  'toggleTrash': [];
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
