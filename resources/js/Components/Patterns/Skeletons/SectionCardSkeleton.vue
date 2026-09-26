<template>
  <div
    data-slot="section-card-skeleton"
    :class="cn('animate-pulse border border-border bg-card p-4 sm:p-5 space-y-4', props.class)"
  >
    <!-- Card Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="size-4 bg-secondary" />
        <div class="h-5 w-32 bg-secondary" />
        <div class="size-5 border border-border bg-secondary/70" />
      </div>
      <div class="h-4 w-16 bg-secondary/50" />
    </div>

    <!-- Card Items -->
    <div class="divide-y divide-border border-t border-border">
      <div
        v-for="n in items"
        :key="n"
        class="flex items-center justify-between py-3"
      >
        <div class="space-y-1.5 flex-1 pr-4">
          <div
            class="h-4 bg-secondary"
            :style="{ width: `${40 + ((n * 17) % 35)}%` }"
          />
          <div
            class="h-3 bg-secondary/60"
            :style="{ width: `${20 + ((n * 13) % 25)}%` }"
          />
        </div>
        <div class="h-4 w-12 bg-secondary/50 shrink-0" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  items?: number;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  items: 3,
  class: undefined,
});
</script>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>
