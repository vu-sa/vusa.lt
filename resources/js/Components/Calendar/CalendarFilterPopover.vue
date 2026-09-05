<template>
  <PublicFilterPopover
    v-bind="$props"
    @toggle="emit('toggle', $event)"
    @clear="emit('clear')"
  />
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import PublicFilterPopover, { type FilterOption } from '@/Components/Public/Base/PublicFilterPopover.vue';

export type { FilterOption };

withDefaults(defineProps<{
  label: string;
  options: FilterOption[];
  selected: string[];
  searchable?: boolean;
  searchPlaceholder?: string;
  triggerClass?: HTMLAttributes['class'];
}>(), {
  searchPlaceholder: '',
  triggerClass: undefined,
});

const emit = defineEmits<{
  (e: 'toggle', value: string): void;
  (e: 'clear'): void;
}>();
</script>
