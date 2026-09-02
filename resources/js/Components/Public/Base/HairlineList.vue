<template>
  <component
    :is="as"
    :class="cn('divide-y divide-border border-y border-border', props.class)"
    data-slot="hairline-list"
  >
    <slot />
  </component>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * A list separated by 1px rules instead of cards.
 *
 * This is the public design's answer to "a list of things": whitespace and hairlines do the
 * grouping, so a page of ten items reads as one column rather than ten boxes. Fill it with
 * `HairlineRow`, or with `<li>`s of your own when the row shape does not fit.
 */
const props = withDefaults(defineProps<{
  /** `ul` when the children are `HairlineRow as="li"`; `div` for arbitrary content. */
  as?: string;
  class?: HTMLAttributes['class'];
}>(), {
  as: 'div',
  class: undefined,
});
</script>
