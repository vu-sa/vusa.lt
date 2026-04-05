<template>
  <StepperIndicator
    v-slot="slotProps"
    v-bind="forwarded"
    :class="cn(
      'inline-flex items-center justify-center rounded-full text-zinc-500/50 w-10 h-10 dark:text-zinc-400/50',
      // Disabled
      'group-data-[disabled]:text-zinc-500 group-data-[disabled]:opacity-50 dark:group-data-[disabled]:text-zinc-400',
      // Active
      'group-data-[state=active]:bg-zinc-900 group-data-[state=active]:text-zinc-50 dark:group-data-[state=active]:bg-zinc-50 dark:group-data-[state=active]:text-zinc-900',
      // Completed
      'group-data-[state=completed]:bg-zinc-100 group-data-[state=completed]:text-zinc-900 dark:group-data-[state=completed]:bg-zinc-800 dark:group-data-[state=completed]:text-zinc-50',
      props.class,
    )"
  >
    <slot v-bind="slotProps" />
  </StepperIndicator>
</template>

<script lang="ts" setup>
import type { StepperIndicatorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { StepperIndicator, useForwardProps } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<StepperIndicatorProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>
