<template>
  <div
    :class="cn(rule && 'border-l-2 border-brand pl-5 sm:pl-6', props.class)"
    data-slot="display-heading"
  >
    <EyebrowLabel v-if="eyebrow || $slots.eyebrow" class="mb-3">
      <slot name="eyebrow">
        {{ eyebrow }}
      </slot>
    </EyebrowLabel>

    <component :is="as" :class="cn('u-display text-balance', sizeClass)">
      <slot>{{ title }}</slot>
    </component>

    <p v-if="lead || $slots.lead" class="mt-5 max-w-2xl leading-relaxed text-muted-foreground">
      <slot name="lead">
        {{ lead }}
      </slot>
    </p>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import EyebrowLabel from './EyebrowLabel.vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * The signature device of the public design: a headline block hung off a brand-coloured vertical
 * rule, echoing the divider in the VU SA key visuals. Eyebrow, headline and lead are one unit
 * because the rule has to span all three — hand-rolling that is how the offsets drift apart.
 *
 * Every part has both a prop and a slot: the prop covers plain text, the slot covers a headline
 * that needs markup inside it.
 */
const props = withDefaults(defineProps<{
  title?: string;
  eyebrow?: string;
  lead?: string;
  /** Heading level. Set it for document structure; `size` controls how big it looks. */
  as?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  /** The brand rule. Off for headings that sit inside an already-ruled block. */
  rule?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  title: undefined,
  eyebrow: undefined,
  lead: undefined,
  as: 'h2',
  size: 'md',
  rule: true,
  class: undefined,
});

/**
 * Size is deliberately independent of `as`: a section headline and a page title are often both
 * `h2`/`h1` semantically but must not be the same visual weight.
 */
const sizeClass = computed(() => ({
  sm: 'text-2xl sm:text-3xl',
  md: 'text-3xl sm:text-4xl lg:text-5xl',
  lg: 'text-4xl sm:text-5xl lg:text-6xl',
  xl: 'text-5xl sm:text-6xl lg:text-7xl',
}[props.size]));
</script>
