<template>
  <component
    :is="as"
    :class="cn('u-eyebrow block', props.class)"
    data-slot="eyebrow-label"
  >
    <slot>{{ text }}</slot>
  </component>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * The small brand-coloured kicker that sits above a display headline.
 *
 * `.u-eyebrow` (app.css) owns the type treatment — bold, uppercase, wide tracking, `text-brand`
 * — so the label reads the same everywhere and changing it is a one-line edit.
 */
const props = withDefaults(defineProps<{
  /** Convenience for the common case; the default slot wins when both are given. */
  text?: string;
  /** `p` by default: an eyebrow labels the heading below it, it is not a heading itself. */
  as?: string;
  class?: HTMLAttributes['class'];
}>(), {
  text: undefined,
  as: 'p',
  class: undefined,
});
</script>
