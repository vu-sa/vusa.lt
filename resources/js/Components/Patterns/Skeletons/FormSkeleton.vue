<template>
  <div
    data-slot="form-skeleton"
    :class="cn('animate-pulse max-w-(--measure-form,40rem) space-y-6', props.class)"
  >
    <!-- Section Question & Intro -->
    <div class="space-y-2 border-b border-border pb-4">
      <div class="h-6 w-52 bg-secondary" />
      <div class="h-4 w-80 max-w-full bg-secondary/70" />
    </div>

    <!-- Form Fields -->
    <div class="space-y-5">
      <div
        v-for="n in fields"
        :key="n"
        class="space-y-1.5"
      >
        <!-- Field label -->
        <div
          class="h-4 bg-secondary"
          :style="{ width: `${80 + ((n * 19) % 50)}px` }"
        />

        <!-- Input box -->
        <div class="h-10 w-full border border-border bg-secondary/40" />

        <!-- Hint -->
        <div
          class="h-3 bg-secondary/60"
          :style="{ width: `${140 + ((n * 23) % 80)}px` }"
        />
      </div>
    </div>

    <!-- Sticky Save Bar skeleton -->
    <div class="flex items-center justify-between border-t border-border pt-4">
      <div class="h-4 w-32 bg-secondary/50" />
      <div class="h-9 w-28 bg-secondary/90" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  fields?: number;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  fields: 4,
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
