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

import { cn } from '@/Utils/Shadcn/utils';

import { cardVariants, type CardVariants } from './index';

const props = withDefaults(defineProps<{
  variant?: CardVariants['variant'];
  class?: HTMLAttributes['class'];
}>(), {
  variant: 'default',
  class: undefined,
});
</script>
