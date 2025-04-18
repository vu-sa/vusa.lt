<docs>
# Changes

- Updated the background color from `bg-border` to `bg-zinc-100` for better visual consistency.
- Increased margins for better spacing between content sections (from my-1/mx-2 to my-6/mx-4).
</docs>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'
import { reactiveOmit } from '@vueuse/core'
import { Separator, type SeparatorProps } from 'reka-ui'

const props = withDefaults(defineProps<
  SeparatorProps & { class?: HTMLAttributes['class'] }
>(), {
  orientation: 'horizontal',
  decorative: true,
})

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <Separator
    data-slot="separator-root"
    v-bind="delegatedProps"
    :class="
      cn(
        `bg-zinc-100 dark:bg-zinc-900 data-[orientation=horizontal]:my-6 data-[orientation=vertical]:mx-4 shrink-0 data-[orientation=horizontal]:h-px data-[orientation=horizontal]:w-full data-[orientation=vertical]:h-full data-[orientation=vertical]:w-px`,
        props.class,
      )
    "
  />
</template>
