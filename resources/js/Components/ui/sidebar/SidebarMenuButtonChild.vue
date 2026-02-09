<template>
  <Primitive
    data-sidebar="menu-button"
    :data-size="size"
    :data-active="isActive"
    :class="cn(sidebarMenuButtonVariants({ variant, size }), props.class)"
    :as
    :as-child
    v-bind="$attrs"
  >
    <slot />
  </Primitive>
</template>

<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';

import type { SidebarMenuButtonVariants } from '.';
import { sidebarMenuButtonVariants } from '.';

import { cn } from '@/Utils/Shadcn/utils';

export interface SidebarMenuButtonProps extends PrimitiveProps {
  variant?: SidebarMenuButtonVariants['variant'];
  size?: SidebarMenuButtonVariants['size'];
  isActive?: boolean;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<SidebarMenuButtonProps>(), {
  as: 'button',
  variant: 'default',
  size: 'default',
});
</script>
