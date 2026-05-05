<template>
  <div class="absolute top-full left-0 isolate z-50 flex justify-center">
    <NavigationMenuViewport
      data-slot="navigation-menu-viewport"
      v-bind="forwardedProps"
      :class="
        cn(
          'origin-top-center bg-white text-zinc-950 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-90 relative mt-1.5 h-[var(--reka-navigation-menu-viewport-height)] w-full overflow-hidden rounded-md border border-zinc-200 shadow md:w-[var(--reka-navigation-menu-viewport-width)] dark:bg-zinc-950 dark:text-zinc-50 dark:border-zinc-800',
          props.class,
        )
      "
    />
  </div>
</template>

<script setup lang="ts">
import {
  NavigationMenuViewport,
  type NavigationMenuViewportProps,
  useForwardProps,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<NavigationMenuViewportProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
