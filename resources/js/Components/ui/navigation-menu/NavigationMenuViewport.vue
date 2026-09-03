<template>
  <!-- The panel spans the header measure rather than hugging its trigger, which is what makes it
       read as one bar-wide mega menu instead of a dropdown. `MainMenu` makes the NavigationMenu
       root `static` so this anchors to the header container; the inset gutters here mirror that
       container's own padding (`px-5 sm:px-6 lg:px-8`), so the panel's edges line up with the
       logo and the utility cluster above it. Change one, change the other. -->
  <div class="absolute inset-x-5 top-full isolate z-50 flex justify-center sm:inset-x-6 lg:inset-x-8">
    <NavigationMenuViewport
      data-slot="navigation-menu-viewport"
      v-bind="forwardedProps"
      :class="
        cn(
          'origin-top-center relative h-[var(--reka-navigation-menu-viewport-height)] w-full overflow-hidden'
          + ' border border-border bg-popover text-popover-foreground shadow-2xl'
          + ' data-[state=open]:animate-in data-[state=closed]:animate-out'
          + ' data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-90',
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
