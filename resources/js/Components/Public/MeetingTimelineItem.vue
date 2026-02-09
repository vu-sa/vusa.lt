<template>
  <div class="relative pl-8">
    <!-- Timeline dot with vote alignment color -->
    <div
      class="absolute left-0 top-3 h-3 w-3 rounded-full border-2 border-background"
      :class="voteAlignmentDotColor"
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
  // Vote alignment: 'aligned' = all student votes match decisions,
  // 'mixed' = some match some don't, 'misaligned' = none match,
  // 'no_data' = no vote data available
  voteAlignment?: 'aligned' | 'mixed' | 'misaligned' | 'no_data' | string;
}>(), {
  isLast: false,
  voteAlignment: 'no_data',
});

const voteAlignmentDotColor = computed(() => {
  return {
    aligned: 'bg-green-500 dark:bg-green-400', // All student votes accepted
    mixed: 'bg-amber-500 dark:bg-amber-400', // Some matches, some mismatches
    misaligned: 'bg-red-500 dark:bg-red-400', // No student votes accepted
    no_data: 'bg-zinc-400 dark:bg-zinc-500', // No vote data to compare
  }[props.voteAlignment] || 'bg-zinc-400';
});
</script>
