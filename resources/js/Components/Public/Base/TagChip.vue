<template>
  <component
    :is="href ? 'a' : 'span'"
    :href
    :class="cn(
      'inline-flex items-center px-2.5 py-1 text-[11px] font-bold uppercase tracking-[0.14em]',
      variantClass,
      href && 'transition-colors',
      props.class,
    )"
    data-slot="tag-chip"
  >
    <slot>{{ label }}</slot>
  </component>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * The category marker on a news card, event tile or article header. Square by design — the
 * public surface zeroes the radius scale, but a chip is exactly the shape people reach for
 * `rounded-full` on, so it is spelled out here once instead of per caller.
 */
const props = withDefaults(defineProps<{
  label?: string;
  /** Renders an `<a>` when given. A resolved URL — this tier never calls `route()`. */
  href?: string;
  /** `solid` is the one-accent-per-view marker; `outline` and `muted` are for lists of many. */
  variant?: 'solid' | 'outline' | 'muted';
  class?: HTMLAttributes['class'];
}>(), {
  label: undefined,
  href: undefined,
  variant: 'solid',
  class: undefined,
});

const variantClass = computed(() => ({
  solid: 'bg-brand-fill text-brand-foreground',
  outline: 'border border-brand text-brand hover:bg-brand/10',
  muted: 'border border-border text-muted-foreground hover:border-brand hover:text-brand',
}[props.variant]));
</script>
