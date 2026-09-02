<template>
  <figure
    :class="cn('relative w-full overflow-hidden bg-secondary', ratioClass, props.class)"
    data-slot="media-frame"
  >
    <img
      v-if="src"
      :src
      :alt
      :loading="eager ? 'eager' : 'lazy'"
      :class="cn(
        'absolute inset-0 size-full object-cover',
        grayscale && 'grayscale',
        hoverZoom && 'transition-transform duration-500 group-hover:scale-[1.03]',
      )"
    >
    <!-- Left-weighted, so copy laid over the image stays legible whatever the photo is doing. -->
    <div v-if="scrim" class="absolute inset-0 bg-gradient-to-r from-black/80 via-black/40 to-transparent" />
    <div v-if="$slots.default" class="absolute inset-0">
      <slot />
    </div>
  </figure>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

/**
 * A fixed-ratio image frame.
 *
 * The default is 16:10 on purpose: VU SA's news and event photography is shot landscape, and a
 * square crop cuts the subject out of it. Reach for a smaller frame rather than a squarer one.
 * `grayscale` is the house treatment wherever an image sits behind or beside type, so the
 * typography stays the loudest thing on the page.
 */
const props = withDefaults(defineProps<{
  src?: string;
  /** Always describe the picture. Pass an empty string only for genuinely decorative images. */
  alt?: string;
  ratio?: '16/10' | '16/9' | '4/3' | '3/2';
  grayscale?: boolean;
  /** Dark gradient for text laid over the image. */
  scrim?: boolean;
  /** Opt out of lazy loading for above-the-fold imagery such as a hero. */
  eager?: boolean;
  /** Pairs with a `group` class on an ancestor link. */
  hoverZoom?: boolean;
  class?: HTMLAttributes['class'];
}>(), {
  src: undefined,
  alt: '',
  ratio: '16/10',
  grayscale: true,
  class: undefined,
});

const ratioClass = computed(() => ({
  '16/10': 'aspect-[16/10]',
  '16/9': 'aspect-[16/9]',
  '4/3': 'aspect-[4/3]',
  '3/2': 'aspect-[3/2]',
}[props.ratio]));
</script>
