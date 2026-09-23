<template>
  <div v-if="facet.type === 'year-pills'" class="flex flex-wrap gap-2 p-1" data-slot="collection-facet-options">
    <button
      v-for="value in facet.values"
      :key="value.value"
      type="button"
      :aria-pressed="value.isSelected"
      :class="[controlVariants({ size: 'sm', active: value.isSelected }), 'tabular-nums']"
      @click="emit('toggle', facet.field, value.value)"
    >
      {{ value.label }}
    </button>
  </div>

  <ul v-else class="flex flex-col" data-slot="collection-facet-options">
    <li v-for="value in facet.values" :key="value.value">
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
        <span class="text-xs tabular-nums text-muted-foreground">{{ value.count }}</span>
      </button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Check } from 'lucide-vue-next';

import { controlVariants } from '@/Components/ui/control';
import type { CollectionFacet } from '@/Composables/useCollectionSource';

defineProps<{
  facet: CollectionFacet;
}>();

const emit = defineEmits<{
  toggle: [field: string, value: string];
}>();
</script>
