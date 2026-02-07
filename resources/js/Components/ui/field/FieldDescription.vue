<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <p
    data-slot="field-description"
    :class="cn(
      'text-zinc-500 text-sm leading-normal font-normal group-has-[[data-orientation=horizontal]]/field:text-balance dark:text-zinc-400',
      'last:mt-0 nth-last-2:-mt-1 [[data-variant=legend]+&]:-mt-1.5',
      '[&>a:hover]:text-zinc-900 [&>a]:underline [&>a]:underline-offset-4 dark:[&>a:hover]:text-zinc-50',
      props.class,
    )"
  >
    <slot />
  </p>
</template>
