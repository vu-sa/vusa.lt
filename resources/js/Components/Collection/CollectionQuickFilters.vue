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
      :class="controlVariants({ size: 'sm', active: filter.active })"
      @click="emit('toggle', filter.id)"
    >
      <span>{{ filter.label }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { CollectionQuickFilter } from './types';

import { controlVariants } from '@/Components/ui/control';

defineProps<{
  filters: CollectionQuickFilter[];
}>();

const emit = defineEmits<{
  toggle: [id: string];
}>();
</script>
