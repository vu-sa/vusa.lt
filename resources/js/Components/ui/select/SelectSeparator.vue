<script setup lang="ts">
import { cn } from '@/Utils/Shadcn/utils'
import { SelectSeparator, type SelectSeparatorProps } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<SelectSeparatorProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <SelectSeparator
    data-slot="select-separator"
    v-bind="delegatedProps"
    :class="cn('bg-zinc-200 pointer-events-none -mx-1 my-1 h-px dark:bg-zinc-800', props.class)"
  />
</template>
