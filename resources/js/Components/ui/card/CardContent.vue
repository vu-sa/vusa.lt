<template>
  <div
    data-slot="card-content"
    :class="cn(cardContentVariants({ size: props.size }), props.class)"
  >
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

import { cn } from '@/Utils/Shadcn/utils';

const cardContentVariants = cva(
  'pt-0 flex-grow',
  {
    variants: {
      size: {
        default: 'p-6 pt-0',
        compact: 'p-4 pt-0',
        sm: 'p-3 pt-0',
      },
    },
    defaultVariants: {
      size: 'default',
    },
  },
);

export type CardContentVariants = VariantProps<typeof cardContentVariants>;

const props = defineProps<{
  size?: 'default' | 'compact' | 'sm';
  class?: HTMLAttributes['class'];
}>();
</script>
