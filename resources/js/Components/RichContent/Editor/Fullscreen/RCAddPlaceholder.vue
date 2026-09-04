<template>
  <button
    type="button"
    :class="cn(
      'absolute z-20 size-3 rounded-full bg-vusa-red shadow-[0_0_0_4px_rgb(var(--vusa-red)/0.2)] animate-pulse transition-transform hover:scale-125 focus-visible:scale-125 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-vusa-red focus-visible:ring-offset-2',
      props.class,
    )"
    data-rc-interactive
    :aria-label="label"
    :title="label"
    @click="$emit('click')"
  >
    <component :is="icon" v-if="icon" class="sr-only" />
    <span class="sr-only">{{ label }}</span>
  </button>
</template>

<script setup lang="ts">
/**
 * Compact add affordance overlaid on the rendered content, so edit-only controls never
 * alter the published layout.
 */
import type { Component, HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  label: string;
  icon?: Component;
  class?: HTMLAttributes['class'];
}>();

defineEmits<{
  (e: 'click'): void;
}>();
</script>
