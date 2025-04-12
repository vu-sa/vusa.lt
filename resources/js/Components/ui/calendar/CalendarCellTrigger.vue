<script lang="ts" setup>
import { cn } from '@/Utils/Shadcn/utils'
import { buttonVariants } from '@/Components/ui/button'
import { CalendarCellTrigger, type CalendarCellTriggerProps, useForwardProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = withDefaults(defineProps<CalendarCellTriggerProps & { class?: HTMLAttributes['class'] }>(), {
  as: 'button',
})

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <CalendarCellTrigger
    data-slot="calendar-cell-trigger"
    :class="cn(
      buttonVariants({ variant: 'ghost' }),
      'size-8 p-0 font-normal aria-selected:opacity-100 cursor-default',
      '[&[data-today]:not([data-selected])]:bg-zinc-100 [&[data-today]:not([data-selected])]:text-zinc-900 dark:[&[data-today]:not([data-selected])]:bg-zinc-800 dark:[&[data-today]:not([data-selected])]:text-zinc-50',
      // Selected
      'data-[selected]:bg-zinc-900 data-[selected]:text-zinc-50 data-[selected]:opacity-100 data-[selected]:hover:bg-zinc-900 data-[selected]:hover:text-zinc-50 data-[selected]:focus:bg-zinc-900 data-[selected]:focus:text-zinc-50 dark:data-[selected]:bg-zinc-50 dark:data-[selected]:text-zinc-900 dark:data-[selected]:hover:bg-zinc-50 dark:data-[selected]:hover:text-zinc-900 dark:data-[selected]:focus:bg-zinc-50 dark:data-[selected]:focus:text-zinc-900',
      // Disabled
      'data-[disabled]:text-zinc-500 data-[disabled]:opacity-50 dark:data-[disabled]:text-zinc-400',
      // Unavailable
      'data-[unavailable]:text-zinc-50 data-[unavailable]:line-through dark:data-[unavailable]:text-zinc-50',
      // Outside months
      'data-[outside-view]:text-zinc-500 dark:data-[outside-view]:text-zinc-400',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarCellTrigger>
</template>
