<script lang="ts" setup>
import { buttonVariants } from '@/Components/ShadcnVue/ui/button'
import { cn } from '@/Utils/shadcn'
import { CalendarCellTrigger, type CalendarCellTriggerProps, useForwardProps } from 'radix-vue'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<CalendarCellTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <CalendarCellTrigger
    :class="cn(
      buttonVariants({ variant: 'ghost' }),
      'h-9 w-9 p-0 font-normal',
      '[&[data-today]:not([data-selected])]:bg-zinc-100 [&[data-today]:not([data-selected])]:text-zinc-900 dark:[&[data-today]:not([data-selected])]:bg-zinc-800 dark:[&[data-today]:not([data-selected])]:text-zinc-50',
      // Selected
      'data-[selected]:bg-zinc-900 data-[selected]:text-zinc-50 data-[selected]:opacity-100 data-[selected]:hover:bg-zinc-900 data-[selected]:hover:text-zinc-50 data-[selected]:focus:bg-zinc-900 data-[selected]:focus:text-zinc-50 dark:data-[selected]:bg-zinc-50 dark:data-[selected]:text-zinc-900 dark:data-[selected]:hover:bg-zinc-50 dark:data-[selected]:hover:text-zinc-900 dark:data-[selected]:focus:bg-zinc-50 dark:data-[selected]:focus:text-zinc-900',
      // Disabled
      'data-[disabled]:text-zinc-500 data-[disabled]:opacity-50 dark:data-[disabled]:text-zinc-400',
      // Unavailable
      'data-[unavailable]:text-zinc-50 data-[unavailable]:line-through dark:data-[unavailable]:text-zinc-50',
      // Outside months
      'data-[outside-view]:text-zinc-500 data-[outside-view]:opacity-50 [&[data-outside-view][data-selected]]:bg-zinc-100/50 [&[data-outside-view][data-selected]]:text-zinc-500 [&[data-outside-view][data-selected]]:opacity-30 dark:data-[outside-view]:text-zinc-400 dark:[&[data-outside-view][data-selected]]:bg-zinc-800/50 dark:[&[data-outside-view][data-selected]]:text-zinc-400',
      props.class,
    )"
    v-bind="forwardedProps"
  >
    <slot />
  </CalendarCellTrigger>
</template>
