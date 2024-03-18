<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/shadcn'

const props = defineProps<{
  class?: HTMLAttributes['class']
  variant?: 'soft' | 'outline',
}>()
</script>

<template>
  <div
    :class="
      cn(
        'rounded-md',
        props.class,
        {
          'border-[1.2px] border-zinc-800/80 dark:border-zinc-500/70': props.variant === 'outline',
          'border-0 bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-zinc-50': props.variant === 'soft',
        },
      )
    "
  >
    <slot />
  </div>
</template>
