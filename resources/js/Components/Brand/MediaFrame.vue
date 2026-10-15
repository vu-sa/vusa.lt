<template>
  <figure
    :class="cn('relative w-full overflow-hidden bg-secondary', !src && 'border border-border', ratioClass, props.class)"
    data-slot="media-frame"
  >
    <img
      v-if="src"
      :src :alt
      :loading="eager ? 'eager' : 'lazy'"
      :fetchpriority="eager ? 'high' : undefined"
      :class="cn('absolute inset-0 size-full object-cover', grayscale && 'grayscale', hoverZoom && 'transition-transform duration-400 group-hover:scale-103')"
      :style="focalPoint ? { objectPosition: focalPoint } : undefined"
    >
    <div v-else-if="$slots.fallback" class="absolute inset-0 flex items-center justify-center">
      <slot name="fallback" />
    </div>
    <div v-if="scrim" class="absolute inset-0 bg-gradient-to-r from-ink/80 via-ink/40 to-transparent" />
    <div v-if="$slots.default" class="absolute inset-0">
      <slot />
    </div>
  </figure>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  src?: string;
  alt?: string;
  ratio?: '16/10' | '16/9' | '4/3' | '3/2';
  grayscale?: boolean;
  scrim?: boolean;
  eager?: boolean;
  hoverZoom?: boolean;
  focalPoint?: string | null;
  class?: HTMLAttributes['class'];
}>(), { src: undefined, alt: '', ratio: '16/10', grayscale: true, focalPoint: null, class: undefined });

const ratioClass = computed(() => ({
  '16/10': 'aspect-[16/10]',
  '16/9': 'aspect-[16/9]',
  '4/3': 'aspect-[4/3]',
  '3/2': 'aspect-[3/2]',
}[props.ratio]));
</script>
