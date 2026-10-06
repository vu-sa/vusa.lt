<template>
  <SelectTrigger
    data-slot="select-trigger"
    :data-size="size"
    v-bind="forwardedProps"
    :class="cn(selectTriggerVariants({ variant, size }), props.class)"
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="size-4 opacity-50" />
    </SelectIcon>
  </SelectTrigger>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import { SelectIcon, SelectTrigger, type SelectTriggerProps, useForwardProps } from 'reka-ui';

import { selectTriggerVariants, type SelectTriggerVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(
  defineProps<SelectTriggerProps & {
    class?: HTMLAttributes['class'];
    variant?: SelectTriggerVariants['variant'];
    size?: SelectTriggerVariants['size'];
  }>(),
  {
    class: undefined,
    variant: 'default',
    size: 'default',
  },
);

const delegatedProps = reactiveOmit(props, 'class', 'variant', 'size');
const forwardedProps = useForwardProps(delegatedProps);
</script>
