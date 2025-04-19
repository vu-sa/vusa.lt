<script lang="ts" setup>
import type { StepperIndicatorProps } from 'reka-ui'
import { cn } from '@/Utils/Shadcn/utils'
import { StepperIndicator, useForwardProps } from 'reka-ui'

import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<StepperIndicatorProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <StepperIndicator
    v-bind="forwarded"
    :class="cn(
      'inline-flex items-center justify-center rounded-full text-zinc-500/50 w-8 h-8 dark:text-zinc-400/50',
      // Disabled
      'group-data-[disabled]:text-zinc-500 group-data-[disabled]:opacity-50 dark:group-data-[disabled]:text-zinc-400',
      // Active
      'group-data-[state=active]:bg-zinc-900 group-data-[state=active]:text-zinc-50 dark:group-data-[state=active]:bg-zinc-50 dark:group-data-[state=active]:text-zinc-900',
      // Completed
      'group-data-[state=completed]:bg-zinc-100 group-data-[state=completed]:text-zinc-900 dark:group-data-[state=completed]:bg-zinc-800 dark:group-data-[state=completed]:text-zinc-50',
      props.class,
    )"
  >
    <slot />
  </StepperIndicator>
</template>
