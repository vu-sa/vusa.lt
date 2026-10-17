<template>
  <img
    v-if="source"
    :src="source"
    :srcset="srcset ?? undefined"
    :sizes="srcset ? sizes : undefined"
    :width="image?.width ?? undefined"
    :height="image?.height ?? undefined"
    :alt="alt || image?.alt || ''"
    :loading="eager ? 'eager' : 'lazy'"
    :fetchpriority="eager ? 'high' : undefined"
    decoding="async"
    :style="objectPosition ? { objectPosition } : undefined"
    data-slot="media-image"
  >
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { cmsSrcset, type ImageData } from '@/Types/media';

/**
 * An image from the media library: srcset from the shared conversions, the focal point as
 * object-position. `src` covers callers that only have a plain URL; file-manager URLs get a
 * srcset from the `/img` resize route.
 */
const props = withDefaults(defineProps<{
  image?: ImageData | null;
  src?: string | null;
  /** Rendered width hint for srcset, e.g. "(min-width: 1024px) 33vw, 100vw". */
  sizes?: string;
  alt?: string | null;
  eager?: boolean;
  /** Overrides the image's own focal point. */
  focalPoint?: string | null;
}>(), { image: null, src: null, sizes: '100vw', alt: null, focalPoint: null });

const source = computed(() => props.image?.url ?? props.src ?? null);
const srcset = computed(() => props.image?.srcset ?? cmsSrcset(source.value));
const objectPosition = computed(() => props.focalPoint ?? props.image?.focal_point ?? null);
</script>
