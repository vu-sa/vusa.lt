<template>
  <div class="relative pl-8">
    <!-- Timeline dot with status color -->
    <div
      class="absolute left-0 top-3 h-3 w-3 rounded-full border-2 border-background"
      :class="statusDotColor"
    />

    <!-- Timeline line (connects dots) -->
    <div
      v-if="!isLast"
      class="absolute left-[5px] top-6 w-0.5 h-[calc(100%+0.75rem)] bg-border"
    />

    <!-- Content slot -->
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
  isLast?: boolean;
  status?: 'complete' | 'incomplete' | 'no_items' | string;
}>(), {
  isLast: false,
  status: 'no_items'
});

const statusDotColor = computed(() => {
  return {
    'complete': 'bg-green-500 dark:bg-green-400',
    'incomplete': 'bg-amber-500 dark:bg-amber-400',
    'no_items': 'bg-zinc-400 dark:bg-zinc-500',
  }[props.status] || 'bg-zinc-400';
});
</script>
