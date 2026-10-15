<template>
  <div
    data-slot="record-skeleton"
    :class="cn('animate-pulse space-y-6', props.class)"
  >
    <!-- Title band skeleton -->
    <div class="space-y-3 pb-6 border-b border-border">
      <!-- Eyebrow -->
      <div class="h-3 w-28 bg-secondary/80" />

      <!-- Title + primary action row -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
          <div class="size-10 shrink-0 border border-border bg-secondary" />
          <div class="h-7 w-64 bg-secondary sm:w-80" />
        </div>

        <div class="flex items-center gap-2">
          <div class="h-6 w-24 border border-border bg-secondary/70" />
          <div class="h-9 w-28 bg-secondary/90" />
        </div>
      </div>
    </div>

    <!-- Key facts strip -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 border border-border bg-card p-4">
      <div
        v-for="i in 4"
        :key="i"
        class="space-y-1.5"
      >
        <div class="h-3 w-16 bg-secondary/60" />
        <div class="h-4 w-28 bg-secondary" />
      </div>
    </div>

    <!-- Main record section card -->
    <div class="border border-border bg-card p-5 space-y-4">
      <div class="h-5 w-36 bg-secondary" />
      <div class="space-y-2">
        <div class="h-4 w-full bg-secondary/70" />
        <div class="h-4 w-5/6 bg-secondary/70" />
        <div class="h-4 w-3/4 bg-secondary/70" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<style scoped>
@media (prefers-reduced-motion: reduce) {
  .animate-pulse {
    animation: none;
  }
}
</style>
