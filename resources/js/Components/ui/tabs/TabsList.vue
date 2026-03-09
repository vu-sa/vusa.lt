<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { TabsList, type TabsListProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<TabsListProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="delegatedProps"
    :class="cn(
      'bg-zinc-100 text-zinc-500 inline-flex h-9 w-full sm:w-fit items-center justify-start sm:justify-center rounded-lg p-[3px] dark:bg-zinc-800 dark:text-zinc-400 overflow-x-auto scrollbar-none',
      props.class,
    )"
  >
    <slot />
  </TabsList>
</template>
