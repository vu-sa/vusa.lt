<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from '@/Utils/Shadcn/utils'
import { Input } from '@/Components/ui/input'

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Input
    data-sidebar="input"
    :class="cn(
      'h-8 w-full bg-white shadow-none focus-visible:ring-2 focus-visible:ring-sidebar-ring dark:bg-zinc-950',
      props.class,
    )"
  >
    <slot />
  </Input>
</template>
