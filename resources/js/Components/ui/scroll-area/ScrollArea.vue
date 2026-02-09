<template>
  <ScrollAreaRoot
    data-slot="scroll-area"
    v-bind="delegatedProps"
    :class="cn('relative', props.class)"
  >
    <ScrollAreaViewport
      data-slot="scroll-area-viewport"
      class="focus-visible:ring-zinc-950/50 size-full rounded-[inherit] transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:outline-1 dark:focus-visible:ring-zinc-300/50"
    >
      <slot />
    </ScrollAreaViewport>
    <!-- Vertical scrollbar (default) -->
    <ScrollBar v-if="showVerticalScrollbar" orientation="vertical" />

    <!-- Horizontal scrollbar -->
    <ScrollBar v-if="showHorizontalScrollbar" orientation="horizontal" />

    <!-- Only show corner when both scrollbars are visible -->
    <ScrollAreaCorner v-if="showHorizontalScrollbar && showVerticalScrollbar" />
  </ScrollAreaRoot>
</template>

<script setup lang="ts">
import {
  ScrollAreaCorner,
  ScrollAreaRoot,
  type ScrollAreaRootProps,
  ScrollAreaViewport,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import ScrollBar from './ScrollBar.vue';

import { cn } from '@/Utils/Shadcn/utils';

// Extend props to include orientation control for horizontal scrolling
const props = withDefaults(defineProps<
  ScrollAreaRootProps & {
    class?: HTMLAttributes['class'];
    orientation?: 'vertical' | 'horizontal' | 'both';
  }
>(), {
  orientation: 'vertical',
});

const delegatedProps = computed(() => {
  const { class: _, orientation: __, ...delegated } = props;

  return delegated;
});

const showHorizontalScrollbar = computed(() => {
  return props.orientation === 'horizontal' || props.orientation === 'both';
});

const showVerticalScrollbar = computed(() => {
  return props.orientation === 'vertical' || props.orientation === 'both';
});
</script>
