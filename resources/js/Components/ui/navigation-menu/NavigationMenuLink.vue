<template>
  <!--
    Deliberately unopinionated: no background, no padding, no text colour.

    shadcn's stock version ships `hover:bg-zinc-100 hover:text-zinc-900` and `p-2 rounded-sm`,
    which is why the mega menu kept rendering as a grid of filled buttons no matter what
    `MainNavigationMenuContent` set — a `hover:` utility here beats an unprefixed one there, and
    its `dark:hover:text-*` beat the consumer's brand colour outright. The panel decides how its
    own rows look; this only has to be a focusable link.

    Safe to strip because the navigation-menu family is used by exactly two components, both in
    the public nav (MainMenu, MainNavigationMenuContent).
  -->
  <NavigationMenuLink
    data-slot="navigation-menu-link"
    v-bind="forwarded"
    :class="cn(
      'flex flex-col gap-1 text-sm transition-colors',
      'focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring',
      props.class,
    )"
  >
    <slot />
  </NavigationMenuLink>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import {
  NavigationMenuLink,
  type NavigationMenuLinkEmits,
  type NavigationMenuLinkProps,
  useForwardPropsEmits,
} from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<NavigationMenuLinkProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<NavigationMenuLinkEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>
