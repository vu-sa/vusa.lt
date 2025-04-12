<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import { reactiveOmit } from '@vueuse/core'
import { ChevronDown } from 'lucide-vue-next'
import { SelectIcon, SelectTrigger, type SelectTriggerProps, useForwardProps } from 'reka-ui'

const props = withDefaults(
  defineProps<SelectTriggerProps & { class?: HTMLAttributes['class'], size?: 'sm' | 'default' }>(),
  { size: 'default' },
)

const delegatedProps = reactiveOmit(props, 'class', 'size')
const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectTrigger
    data-slot="select-trigger"
    :data-size="size"
    v-bind="forwardedProps"
    :class="cn(
      'border border-zinc-300 dark:border-zinc-800',
      'placeholder:text-gray-400',
      '[&_svg:not([class*=\'text-\'])]:text-gray-400',
      'focus-visible:border-zinc-500',
      'focus-visible:ring-zinc-200',
      'aria-invalid:ring-red-100',
      'dark:aria-invalid:ring-red-200',
      'aria-invalid:border-red-500',
      'dark:bg-zinc-800/30',
      'flex w-fit items-center justify-between gap-2',
      'rounded-md bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs',
      'transition-[color,box-shadow] outline-none focus-visible:ring-[3px]',
      'disabled:cursor-not-allowed disabled:opacity-50',
      'data-[size=default]:h-9',
      'data-[size=sm]:h-8',
      '*:data-[slot=select-value]:line-clamp-1',
      '*:data-[slot=select-value]:flex',
      '*:data-[slot=select-value]:items-center',
      '*:data-[slot=select-value]:gap-2',
      '[&_svg]:pointer-events-none',
      '[&_svg]:shrink-0',
      '[&_svg:not([class*=\'size-\'])]:size-4',
      props.class,
    )"
  >
    <slot />
    <SelectIcon as-child>
      <ChevronDown class="size-4 opacity-50" />
    </SelectIcon>
  </SelectTrigger>
</template>
