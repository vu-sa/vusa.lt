<script setup lang="ts">
import type { ComboboxGroupProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ComboboxGroup, ComboboxLabel } from "reka-ui"
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<ComboboxGroupProps & {
  class?: HTMLAttributes["class"]
  heading?: string
}>()

const delegatedProps = reactiveOmit(props, "class")
</script>

<template>
  <ComboboxGroup
    data-slot="combobox-group"
    v-bind="delegatedProps"
    :class="cn('overflow-hidden p-1 text-zinc-950 dark:text-zinc-50', props.class)"
  >
    <ComboboxLabel v-if="heading" class="px-2 py-1.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">
      {{ heading }}
    </ComboboxLabel>
    <slot />
  </ComboboxGroup>
</template>
