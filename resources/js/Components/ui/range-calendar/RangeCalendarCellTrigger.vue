<script lang="ts" setup>
import type { RangeCalendarCellTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { RangeCalendarCellTrigger, useForwardProps } from "reka-ui"
import { cn } from '@/Utils/Shadcn/utils'
import { buttonVariants } from '@/Components/ui/button'

const props = defineProps<RangeCalendarCellTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <RangeCalendarCellTrigger
    :class="cn(
      buttonVariants({ variant: 'ghost' }),
      'h-9 w-9 p-0 font-normal data-[selected]:opacity-100',
      '[&[data-today]:not([data-selected])]:bg-zinc-100 [&[data-today]:not([data-selected])]:text-zinc-900 dark:[&[data-today]:not([data-selected])]:bg-zinc-800 dark:[&[data-today]:not([data-selected])]:text-zinc-50',
      // Selection Start
      'data-[selection-start]:bg-zinc-900 data-[selection-start]:text-zinc-50 data-[selection-start]:hover:bg-zinc-900 data-[selection-start]:hover:text-zinc-50 data-[selection-start]:focus:bg-zinc-900 data-[selection-start]:focus:text-zinc-50 dark:data-[selection-start]:bg-zinc-50 dark:data-[selection-start]:text-zinc-900 dark:data-[selection-start]:hover:bg-zinc-50 dark:data-[selection-start]:hover:text-zinc-900 dark:data-[selection-start]:focus:bg-zinc-50 dark:data-[selection-start]:focus:text-zinc-900',
      // Selection End
      'data-[selection-end]:bg-zinc-900 data-[selection-end]:text-zinc-50 data-[selection-end]:hover:bg-zinc-900 data-[selection-end]:hover:text-zinc-50 data-[selection-end]:focus:bg-zinc-900 data-[selection-end]:focus:text-zinc-50 dark:data-[selection-end]:bg-zinc-50 dark:data-[selection-end]:text-zinc-900 dark:data-[selection-end]:hover:bg-zinc-50 dark:data-[selection-end]:hover:text-zinc-900 dark:data-[selection-end]:focus:bg-zinc-50 dark:data-[selection-end]:focus:text-zinc-900',
      // Outside months
      'data-[outside-view]:text-zinc-500 data-[outside-view]:opacity-50 [&[data-outside-view][data-selected]]:bg-zinc-100/50 [&[data-outside-view][data-selected]]:text-zinc-500 [&[data-outside-view][data-selected]]:opacity-30 dark:data-[outside-view]:text-zinc-400 dark:[&[data-outside-view][data-selected]]:bg-zinc-800/50 dark:[&[data-outside-view][data-selected]]:text-zinc-400',
      // Disabled
      'data-[disabled]:text-zinc-500 data-[disabled]:opacity-50 dark:data-[disabled]:text-zinc-400',
      // Unavailable
      'data-[unavailable]:text-zinc-50 data-[unavailable]:line-through dark:data-[unavailable]:text-zinc-50',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </RangeCalendarCellTrigger>
</template>
