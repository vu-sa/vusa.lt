<script setup lang="ts">
import type { ComboboxSeparatorProps } from 'reka-ui'
import { cn } from '@/Utils/Shadcn/utils'
import { ComboboxSeparator } from 'reka-ui'
import { computed, type HTMLAttributes } from 'vue'

const props = defineProps<ComboboxSeparatorProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})
</script>

<template>
  <ComboboxSeparator
    data-slot="combobox-separator"
    v-bind="delegatedProps"
    :class="cn('bg-zinc-200 -mx-1 h-px dark:bg-zinc-800', props.class)"
  >
    <slot />
  </ComboboxSeparator>
</template>
