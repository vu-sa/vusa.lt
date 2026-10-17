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
import { computed, inject } from 'vue';

import { cmsImageSrcsetsKey, type ImageData } from '@/Types/media';

/**
 * An image from the media library: srcset from the shared conversions, the focal point as
 * object-position. `src` covers plain URLs; CMS candidates come from server-checked source dimensions.
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

const cmsImageSrcsets = inject(cmsImageSrcsetsKey);
const source = computed(() => props.image?.url ?? props.src ?? null);
const srcset = computed(() => props.image?.srcset ?? (source.value ? cmsImageSrcsets?.value[source.value] : null));
const objectPosition = computed(() => props.focalPoint ?? props.image?.focal_point ?? null);
</script>
