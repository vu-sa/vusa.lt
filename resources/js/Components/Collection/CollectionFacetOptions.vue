<template>
  <FacetCountHelp v-if="showCountHelp" />
  <input
    v-if="facet.type !== 'year-pills' && facet.values.length > 8"
    v-model="term"
    type="search"
    :aria-label="$t('search.facet_search')"
    :placeholder="$t('search.facet_search')"
    class="mb-2 min-h-11 w-full border border-border bg-background px-3 text-sm text-foreground"
  >
  <p v-if="loading" role="status" class="px-2 text-xs text-muted-foreground">
    {{ $t('search.facet_search_loading') }}
  </p>
  <p v-if="failed" role="status" class="px-2 text-xs text-muted-foreground">
    {{ $t('search.facet_search_error') }}
  </p>
  <div v-if="facet.type === 'year-pills'" class="flex flex-wrap gap-2 p-1" data-slot="collection-facet-options">
    <button
      v-for="value in searchedValues"
      :key="value.value"
      type="button"
      :aria-pressed="value.isSelected"
      :class="[controlVariants({ size: 'sm', active: value.isSelected, voice: 'sentence' }), 'tabular-nums']"
      @click="emit('toggle', facet.field, value.value)"
    >
      {{ value.label }}
    </button>
  </div>

  <ul v-else class="flex flex-col" data-slot="collection-facet-options">
    <li v-for="value in searchedValues" :key="value.value">
      <button
        type="button"
        role="checkbox"
        :aria-checked="value.isSelected"
        class="flex min-h-9 w-full items-center gap-3 px-2 py-1.5 text-left text-sm hover:bg-secondary pointer-coarse:min-h-11"
        @click="emit('toggle', facet.field, value.value)"
      >
        <span
          :class="[
            'flex size-4 shrink-0 items-center justify-center border',
            value.isSelected ? 'border-brand bg-brand-fill text-brand-foreground' : 'border-border bg-background',
          ]"
          aria-hidden="true"
        >
          <Check v-if="value.isSelected" class="size-3" />
        </span>
        <span class="min-w-0 flex-1 truncate">{{ value.label }}</span>
        <span v-if="value.count !== undefined || value.isSelected" class="text-xs tabular-nums text-muted-foreground">{{ value.count ?? '–' }}</span>
      </button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Check } from 'lucide-vue-next';

import FacetCountHelp from '@/Components/ui/FacetCountHelp.vue';
import { useFacetOptions } from '@/Shared/Search/useFacetOptions';
import { controlVariants } from '@/Components/ui/control';
import type { CollectionFacet } from '@/Composables/useCollectionSource';

const props = withDefaults(defineProps<{
  facet: CollectionFacet;
  showCountHelp?: boolean;
}>(), { showCountHelp: true });

const { term, values: searchedValues, loading, failed } = useFacetOptions(
  () => props.facet.remote ? props.facet.field : undefined,
  () => props.facet.values,
  () => props.facet.values.filter(value => value.isSelected).map(value => value.value),
);

const emit = defineEmits<{
  toggle: [field: string, value: string];
}>();
</script>
