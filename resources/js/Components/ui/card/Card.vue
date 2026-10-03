<template>
  <div
    data-slot="card"
    :class="
      cn(
        cardVariants({ variant }),
        'h-full',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

import { cardVariants, type CardVariants } from './index';

import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  variant?: CardVariants['variant'];
  class?: HTMLAttributes['class'];
}>(), {
  variant: 'default',
  class: undefined,
});
</script>
