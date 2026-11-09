<template>
  <!-- A popover's closing action spans its foot, so it reads as the end of the popover, not one more row. -->
  <button
    type="button"
    :class="cn(
      'flex h-10 w-full items-center justify-center gap-2 bg-secondary/20 pointer-coarse:h-11',
      'text-xs font-bold uppercase tracking-wide transition-colors hover:bg-secondary/60',
      'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
      'disabled:pointer-events-none disabled:opacity-50',
      tone === 'brand' ? 'text-brand' : 'text-muted-foreground hover:text-foreground',
      props.class,
    )"
    data-slot="popover-footer-action"
  >
    <slot />
  </button>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  /** `brand` for the action the popover is for, `muted` for a secondary one beside it. */
  tone?: 'brand' | 'muted';
  class?: HTMLAttributes['class'];
}>(), {
  tone: 'brand',
  class: undefined,
});
</script>
