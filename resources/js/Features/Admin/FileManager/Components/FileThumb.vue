<template>
  <span
    :class="[
      'relative flex items-center justify-center overflow-hidden bg-secondary/40',
      props.class,
    ]"
    data-slot="file-thumb"
  >
    <img
      v-if="showImage"
      :src
      :alt="name"
      loading="lazy"
      decoding="async"
      :class="['size-full', fit === 'contain' ? 'object-contain' : 'object-cover']"
      @error="handleError"
    >
    <component :is="icon" v-else :class="iconClass" aria-hidden="true" />
  </span>
</template>

<script setup lang="ts">
import { computed, ref, watch, type HTMLAttributes } from 'vue';

import { imageSrc, isPreviewableImage, isThumbnailable, publicUrl } from '../utils';

import { getFileIcon } from '@/Utils/fileIcons';

const props = withDefaults(defineProps<{
  path: string;
  name: string;
  width?: 160 | 320 | 640;
  fit?: 'cover' | 'contain';
  iconClass?: string;
  class?: HTMLAttributes['class'];
}>(), {
  width: 320,
  fit: 'cover',
  iconClass: 'size-8 text-muted-foreground',
  class: undefined,
});

/** A failed thumbnail retries the original once (it may predate the resize cache) before the icon. */
const attempt = ref<'resized' | 'original' | 'icon'>('resized');

watch(() => props.path, () => {
  attempt.value = 'resized';
});

const showImage = computed(() => attempt.value !== 'icon' && isPreviewableImage(props.name));
const src = computed(() => attempt.value === 'original' ? publicUrl(props.path) : imageSrc(props.path, props.width));

function handleError() {
  attempt.value = attempt.value === 'resized' && isThumbnailable(props.name) ? 'original' : 'icon';
}
const icon = computed(() => getFileIcon(props.name));
</script>
