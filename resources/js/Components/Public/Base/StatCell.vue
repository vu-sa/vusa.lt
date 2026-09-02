<template>
  <div
    :class="cn('flex flex-col gap-2 px-6 py-8 first:pl-0 last:pr-0', props.class)"
    data-slot="stat-cell"
  >
    <span class="u-display text-4xl text-brand lg:text-5xl">
      <slot name="value">{{ value }}</slot>
    </span>
    <span class="text-sm font-semibold uppercase tracking-[0.14em] text-foreground">
      <slot name="label">{{ label }}</slot>
    </span>
    <span v-if="description || $slots.description" class="text-sm leading-relaxed text-muted-foreground">
      <slot name="description">{{ description }}</slot>
    </span>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * One figure in a stats strip. Meant to sit in a hairline-divided row
 * (`divide-x divide-border`), not in a card — the number is the emphasis, and a box around it
 * competes with the number.
 *
 * The public counterpart to `Patterns/StatCard`, which is a bordered admin KPI tile.
 */
const props = withDefaults(defineProps<{
  value?: string | number;
  label?: string;
  description?: string;
  class?: HTMLAttributes['class'];
}>(), {
  value: undefined,
  label: undefined,
  description: undefined,
  class: undefined,
});
</script>
