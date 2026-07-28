<template>
  <!-- Single root: a fragment/multi-root component can't auto-inherit the width/flush
       class RichContentParser binds via :class (no single target to fall through to).
       `aria-hidden` keeps the empty gap out of the a11y tree — it carries no meaning
       beyond layout, and a screen reader announcing "empty region" between blocks
       would be noise. The canvas's `.rc-flush+*` rule zeros the next sibling's top
       margin, so the rendered height IS the full gap to the next block. -->
  <div :class="sizeClass" aria-hidden="true" data-testid="rc-spacer" />
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { SPACER_SIZE_CLASS, DEFAULT_SPACER_SIZE } from './spacerSizes';

import type { Spacer } from '@/Types/contentParts';

const props = defineProps<{
  element: Spacer;
}>();

const sizeClass = computed(() => {
  const size = props.element.options?.size ?? DEFAULT_SPACER_SIZE;
  return SPACER_SIZE_CLASS[size] ?? SPACER_SIZE_CLASS[DEFAULT_SPACER_SIZE];
});
</script>
