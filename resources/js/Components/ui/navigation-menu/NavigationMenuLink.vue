<template>
  <NavigationMenuLink
    data-slot="navigation-menu-link"
    v-bind="forwarded"
    :class="cn(
      'data-[active=true]:focus:bg-zinc-100',
      'data-[active=true]:hover:bg-zinc-100',
      'data-[active=true]:bg-zinc-50',
      'data-[active=true]:text-zinc-900',
      'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:text-zinc-200',
      'focus:bg-zinc-100 focus:text-zinc-900 dark:focus:text-zinc-200',
      'ring-zinc-100 dark:ring-zinc-200',
      'dark:outline-zinc-300 outline-zinc-200',
      '[&_svg:not([class*=\'text-\'])]:text-gray-400',
      'flex flex-col gap-1 rounded-sm p-2 text-sm transition-[color,box-shadow]',
      'focus-visible:ring-4 focus-visible:outline-1',
      '[&_svg:not([class*=\'size-\'])]:size-4',
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
