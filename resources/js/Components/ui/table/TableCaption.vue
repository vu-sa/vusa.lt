<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/Utils/Shadcn/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <caption
    data-slot="table-caption"
    :class="cn('text-zinc-500 mt-4 text-sm dark:text-zinc-400', props.class)"
  >
    <slot />
  </caption>
</template>
