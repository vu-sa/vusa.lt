<template>
  <StepperDescription v-slot="slotProps" v-bind="forwarded" :class="cn('text-xs text-zinc-500 dark:text-zinc-400', props.class)">
    <slot v-bind="slotProps" />
  </StepperDescription>
</template>

<script lang="ts" setup>
import type { StepperDescriptionProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { StepperDescription, useForwardProps } from 'reka-ui';

import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<StepperDescriptionProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');

const forwarded = useForwardProps(delegatedProps);
</script>
