<template>
  <div class="flex flex-wrap gap-1">
    <TruncatedBadge
      v-for="item in visibleItems"
      :key="itemKey(item)"
      :text="itemLabel(item)"
      variant="outline"
      class="text-xs"
    />
    <TruncatedBadge
      v-if="overflowCount > 0"
      :text="`+${overflowCount}`"
      variant="secondary"
      class="text-xs"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import TruncatedBadge from './TruncatedBadge.vue';

const props = withDefaults(defineProps<{
  items: Array<Record<string, any>>;
  labelKey?: string;
  maxVisible?: number;
}>(), {
  labelKey: 'title',
  maxVisible: 3,
});

const visibleItems = computed(() => props.items.slice(0, props.maxVisible));
const overflowCount = computed(() => Math.max(0, props.items.length - props.maxVisible));

function itemKey(item: Record<string, any>): string | number {
  return item.id ?? item[props.labelKey];
}

function itemLabel(item: Record<string, any>): string {
  const value = item[props.labelKey];
  if (value && typeof value === 'object') {
    return value.lt || value.en || Object.values(value)[0] || '';
  }

  return String(value ?? '');
}
</script>
