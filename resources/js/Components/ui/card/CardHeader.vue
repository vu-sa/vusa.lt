<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import type { VariantProps } from 'class-variance-authority'
import { cn } from '@/Utils/Shadcn/utils'
import { cva } from 'class-variance-authority'

const cardHeaderVariants = cva(
  'flex flex-col space-y-1.5',
  {
    variants: {
      size: {
        default: 'p-6',
        compact: 'p-4 pb-3',
        sm: 'p-3',
      },
    },
    defaultVariants: {
      size: 'default',
    },
  },
)

export type CardHeaderVariants = VariantProps<typeof cardHeaderVariants>

const props = defineProps<{
  size?: 'default' | 'compact' | 'sm'
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card-header"
    :class="cn(cardHeaderVariants({ size: props.size }), props.class)"
  >
    <slot />
  </div>
</template>
