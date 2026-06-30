<template>
  <Primitive
    data-sidebar="group-label"
    :as
    :as-child
    :class="cn(
      'duration-200 flex h-8 shrink-0 items-center rounded-md px-2 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/60 outline-none ring-sidebar-ring transition-[margin,opacity] ease-linear focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0',
      'group-data-[density=compact]/density:h-5 group-data-[density=compact]/density:gap-1 group-data-[density=compact]/density:text-[10px] group-data-[density=compact]/density:[&>svg]:size-3',
      'group-data-[collapsible=icon]:-mt-8 group-data-[collapsible=icon]:opacity-0',
      props.class)"
  >
    <slot />
  </Primitive>
</template>

<script setup lang="ts">
import type { PrimitiveProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { Primitive } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<PrimitiveProps & {
  class?: HTMLAttributes['class'];
}>();
</script>
