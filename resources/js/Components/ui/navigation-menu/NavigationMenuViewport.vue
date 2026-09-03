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
          // Light mode: no border, only `shadow-2xl` separates the panel from the page —
          // this panel routinely holds full-bleed dark photo cards (see
          // MainNavigationMenuContent's media columns) that reach the panel edge, and even a
          // soft light-mode `--border` read as an outline around the photo. Dark mode keeps its
          // border: `--border` there is translucent white, which reads as a subtle frame rather
          // than a seam against dark imagery. High-contrast mode restores the border in light
          // mode too: `--border` becomes near-black there, and a shadow alone isn't enough
          // separation for that preference.
          + ' dark:border dark:border-border/50 [.a11y-contrast_&]:border [.a11y-contrast_&]:border-border'
          + ' bg-popover text-popover-foreground shadow-2xl'
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
