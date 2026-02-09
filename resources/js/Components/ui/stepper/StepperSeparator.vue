<template>
  <StepperSeparator
    v-bind="forwarded"
    :class="cn(
      'bg-zinc-100 dark:bg-zinc-800',
      // Disabled
      'group-data-[disabled]:bg-zinc-100 group-data-[disabled]:opacity-50 dark:group-data-[disabled]:bg-zinc-800',
      // Completed
      'group-data-[state=completed]:bg-zinc-900 dark:group-data-[state=completed]:bg-zinc-50',
      props.class,
    )"
  />
</template>

<script lang="ts" setup>
import type { StepperSeparatorProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { StepperSeparator, useForwardProps } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<StepperSeparatorProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>
