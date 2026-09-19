<template>
  <div
    v-if="filters.length > 0"
    class="flex flex-wrap items-center gap-2"
    role="group"
    :aria-label="$t('Greiti filtrai')"
    data-slot="collection-quick-filters"
  >
    <button
      v-for="filter in filters"
      :key="filter.id"
      type="button"
      :aria-pressed="filter.active"
      :class="[
        'inline-flex h-8 items-center gap-2 border px-3 text-sm pointer-coarse:h-11',
        'transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring',
        filter.active
          ? 'border-brand bg-brand/10 text-brand'
          : 'border-border bg-background text-foreground hover:border-foreground/40',
      ]"
      @click="emit('toggle', filter.id)"
    >
      <span>{{ filter.label }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { CollectionQuickFilter } from './types';

defineProps<{
  filters: CollectionQuickFilter[];
}>();

const emit = defineEmits<{
  toggle: [id: string];
}>();
</script>
