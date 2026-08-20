<template>
  <div
    :class="cn('grid grid-cols-1 items-start gap-6', hasSidebar && 'xl:grid-cols-3', props.class)"
    data-slot="show-page-grid"
  >
    <div :class="cn('space-y-6', hasSidebar && 'xl:col-span-2')">
      <slot name="main" />
    </div>

    <!-- The sidebar sticks on wide viewports so context stays visible while the main column scrolls. -->
    <div v-if="hasSidebar" class="space-y-6 xl:sticky xl:top-6 xl:self-start">
      <slot name="sidebar" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, useSlots } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class'];
}>(), {
  class: undefined,
});

const slots = useSlots();

/**
 * Without a sidebar the main column spans the full width rather than leaving a
 * dead third — so pages with nothing contextual to show still look deliberate.
 */
const hasSidebar = computed(() => Boolean(slots.sidebar));
</script>
