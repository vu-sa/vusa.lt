<template>
  <HoverCardPortal>
    <HoverCardContent
      data-slot="hover-card-content"
      v-bind="forwardedProps"
      :class="
        cn(
          'bg-white text-zinc-950 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 w-64 rounded-md border border-zinc-200 p-4 shadow-md outline-hidden dark:bg-zinc-950 dark:text-zinc-50 dark:border-zinc-800',
          props.class,
        )
      "
    >
      <slot />
    </HoverCardContent>
  </HoverCardPortal>
</template>

<script setup lang="ts">
import {
  HoverCardContent,
  type HoverCardContentProps,
  HoverCardPortal,
  useForwardProps,
} from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(
  defineProps<HoverCardContentProps & { class?: HTMLAttributes['class'] }>(),
  {
    sideOffset: 4,
  },
);

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props;

  return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);
</script>
