<script setup lang="ts">
import type { TagsInputItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TagsInputItem, useForwardProps } from "reka-ui"
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<TagsInputItemProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TagsInputItem v-bind="forwardedProps" :class="cn('flex h-6 items-center rounded bg-zinc-100 data-[state=active]:ring-zinc-950 data-[state=active]:ring-2 data-[state=active]:ring-offset-2 ring-offset-white dark:bg-zinc-800 dark:data-[state=active]:ring-zinc-300 dark:ring-offset-zinc-950', props.class)">
    <slot />
  </TagsInputItem>
</template>
